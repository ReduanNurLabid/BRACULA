-- 1. Create material requests table
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

-- 2. Update rpc_upload_material to support material requests fulfillment
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
