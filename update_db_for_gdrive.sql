-- Run this to update the existing DB to the new features:
-- 1. Profiles: change default credit balance to 2 (doesn't change existing users, just the default)
ALTER TABLE public.profiles ALTER COLUMN credit_balance SET DEFAULT 2;

-- 2. Update the user trigger to give 2 credits
CREATE OR REPLACE FUNCTION public.handle_new_user() 
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO public.profiles (id, email, full_name, credit_balance)
  -- Give 2 downloads instead of 10
  VALUES (new.id, new.email, new.raw_user_meta_data->>'full_name', 2);
  RETURN new;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 3. Add new columns to materials table
ALTER TABLE public.materials 
ADD COLUMN is_gdrive BOOLEAN DEFAULT false NOT NULL,
ADD COLUMN last_accessed_at TIMESTAMP WITH TIME ZONE;

-- 4. Update the rpc_download_material to cost 1 credit and update last_accessed_at
CREATE OR REPLACE FUNCTION rpc_download_material(
    p_material_id UUID,
    p_user_id UUID
) RETURNS boolean AS $$
DECLARE
    current_credits INTEGER;
BEGIN
    -- Check balance
    SELECT credit_balance INTO current_credits FROM public.profiles WHERE id = p_user_id;
    
    IF current_credits < 1 THEN
        RETURN false; -- Not enough downloads
    END IF;

    -- Deduct credits
    UPDATE public.profiles
    SET credit_balance = credit_balance - 1
    WHERE id = p_user_id;

    -- Increment download count & update last accessed (if gdrive)
    UPDATE public.materials
    SET downloads_count = downloads_count + 1,
        last_accessed_at = CASE WHEN is_gdrive = true THEN timezone('utc'::text, now()) ELSE last_accessed_at END
    WHERE id = p_material_id;

    -- Record transaction
    INSERT INTO public.transactions (user_id, amount, reason, reference_id)
    VALUES (p_user_id, -1, 'download_material', p_material_id);

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 5. Create material reports table
CREATE TABLE public.material_reports (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    material_id UUID REFERENCES public.materials(id) ON DELETE CASCADE NOT NULL,
    reporter_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'resolved', 'dismissed')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

ALTER TABLE public.material_reports ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Admins can view all reports" ON public.material_reports FOR SELECT USING (true);
CREATE POLICY "Authenticated users can create reports" ON public.material_reports FOR INSERT WITH CHECK (auth.role() = 'authenticated' AND auth.uid() = reporter_id);

-- 6. RPC for admin to refund downloads
CREATE OR REPLACE FUNCTION rpc_refund_download(
    p_report_id UUID,
    p_reporter_id UUID,
    p_material_id UUID
) RETURNS boolean AS $$
BEGIN
    -- Refund 1 download
    UPDATE public.profiles
    SET credit_balance = credit_balance + 1
    WHERE id = p_reporter_id;

    -- Record transaction
    INSERT INTO public.transactions (user_id, amount, reason, reference_id)
    VALUES (p_reporter_id, 1, 'refund_material', p_material_id);

    -- Update report status
    UPDATE public.material_reports
    SET status = 'resolved'
    WHERE id = p_report_id;

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 7. Update rpc_upload_material to support gdrive flag and material requests
CREATE OR REPLACE FUNCTION rpc_upload_material(
    p_title TEXT,
    p_course_code TEXT,
    p_semester TEXT,
    p_file_url TEXT,
    p_file_type TEXT,
    p_user_id UUID,
    p_is_gdrive BOOLEAN DEFAULT false,
    p_request_id UUID DEFAULT NULL
) RETURNS void AS $$
BEGIN
    -- 1. Insert material
    INSERT INTO public.materials (uploader_id, title, course_code, semester, file_url, file_type, is_gdrive)
    VALUES (p_user_id, p_title, p_course_code, p_semester, p_file_url, p_file_type, p_is_gdrive);

    -- 2. Award 2 downloads
    UPDATE public.profiles
    SET credit_balance = credit_balance + 2
    WHERE id = p_user_id;

    -- 3. Record transaction
    INSERT INTO public.transactions (user_id, amount, reason)
    VALUES (p_user_id, 2, 'upload_material');

    -- 4. Mark request as fulfilled if applicable
    IF p_request_id IS NOT NULL THEN
        UPDATE public.material_requests
        SET status = 'fulfilled',
            fulfilled_by_id = p_user_id
        WHERE id = p_request_id;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 8. Add material requests table
CREATE TABLE public.material_requests (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    requester_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    course_code TEXT NOT NULL,
    description TEXT NOT NULL,
    status TEXT DEFAULT 'open' CHECK (status IN ('open', 'fulfilled', 'cancelled')),
    fulfilled_by_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

ALTER TABLE public.material_requests ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Requests are viewable by everyone" ON public.material_requests FOR SELECT USING (true);
CREATE POLICY "Authenticated users can create requests" ON public.material_requests FOR INSERT WITH CHECK (auth.role() = 'authenticated' AND auth.uid() = requester_id);
CREATE POLICY "Users can cancel their own requests" ON public.material_requests FOR UPDATE USING (auth.uid() = requester_id);

