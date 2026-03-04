-- BRAULA Database Schema Setup
-- Run this in the Supabase SQL Editor

-- 1. Profiles Table (Extends Supabase Auth Users)
CREATE TABLE public.profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    full_name TEXT,
    credit_balance INTEGER DEFAULT 2 NOT NULL, -- Starting balance (Available Downloads) for new users
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Enable RLS for profiles
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;

-- Allow users to view all profiles, but only update their own
CREATE POLICY "Public profiles are viewable by everyone." 
ON public.profiles FOR SELECT USING (true);

CREATE POLICY "Users can insert their own profile." 
ON public.profiles FOR INSERT WITH CHECK (auth.uid() = id);

CREATE POLICY "Users can update own profile." 
ON public.profiles FOR UPDATE USING (auth.uid() = id);

-- Trigger to automatically create a profile when a new user signs up
CREATE OR REPLACE FUNCTION public.handle_new_user() 
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO public.profiles (id, email, full_name, credit_balance)
  VALUES (new.id, new.email, new.raw_user_meta_data->>'full_name', 2);
  RETURN new;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE PROCEDURE public.handle_new_user();


-- 2. Study Materials Table
CREATE TABLE public.materials (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    uploader_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    title TEXT NOT NULL,
    course_code TEXT NOT NULL,
    semester TEXT,
    description TEXT,
    file_url TEXT NOT NULL,
    file_type TEXT NOT NULL,
    is_gdrive BOOLEAN DEFAULT false NOT NULL,
    last_accessed_at TIMESTAMP WITH TIME ZONE,
    downloads_count INTEGER DEFAULT 0 NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Enable RLS for materials
ALTER TABLE public.materials ENABLE ROW LEVEL SECURITY;

-- Allow anyone (authenticated) to view materials
CREATE POLICY "Materials are viewable by everyone." 
ON public.materials FOR SELECT USING (true);

-- Allow authenticated users to insert materials
CREATE POLICY "Users can upload materials." 
ON public.materials FOR INSERT WITH CHECK (auth.role() = 'authenticated');


-- 3. Transactions Ledger Table (For Credit Flow)
CREATE TABLE public.transactions (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    amount INTEGER NOT NULL, -- Positive for earn, Negative for spend
    reason TEXT NOT NULL, -- e.g., 'upload_material', 'download_material'
    reference_id UUID, -- Links to material ID
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Note: Transactions should primarily be handled server-side by Admin Client 
-- to prevent cheating, so we don't allow public inserts via RLS.
ALTER TABLE public.transactions ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Users can view their own transactions" 
ON public.transactions FOR SELECT USING (auth.uid() = user_id);

-- 4. Stored Procedures (RPCs)
CREATE OR REPLACE FUNCTION increment_credit(user_id_param UUID, amount_param INTEGER)
RETURNS void AS $$
BEGIN
  UPDATE public.profiles
  SET credit_balance = credit_balance + amount_param,
      updated_at = timezone('utc'::text, now())
  WHERE id = user_id_param;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE OR REPLACE FUNCTION increment_download_count(material_id_param UUID)
RETURNS void AS $$
BEGIN
  UPDATE public.materials
  SET downloads_count = downloads_count + 1
  WHERE id = material_id_param;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;


-- 5. Community Forums Tables

-- Communities
CREATE TABLE public.communities (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    name TEXT UNIQUE NOT NULL,
    description TEXT,
    created_by UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    is_general BOOLEAN DEFAULT false NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

ALTER TABLE public.communities ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Communities are viewable by everyone." ON public.communities FOR SELECT USING (true);
CREATE POLICY "Authenticated users can create communities." ON public.communities FOR INSERT WITH CHECK (auth.role() = 'authenticated');

-- Posts
CREATE TABLE public.posts (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    community_id UUID REFERENCES public.communities(id) ON DELETE CASCADE NOT NULL,
    author_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    upvotes INTEGER DEFAULT 0 NOT NULL,
    downvotes INTEGER DEFAULT 0 NOT NULL,
    edit_history JSONB DEFAULT '[]'::jsonb NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

ALTER TABLE public.posts ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Posts are viewable by everyone." ON public.posts FOR SELECT USING (true);
CREATE POLICY "Authenticated users can create posts." ON public.posts FOR INSERT WITH CHECK (auth.role() = 'authenticated');
CREATE POLICY "Authors can update their own posts." ON public.posts FOR UPDATE USING (auth.uid() = author_id);
CREATE POLICY "Authors can delete their own posts." ON public.posts FOR DELETE USING (auth.uid() = author_id);

-- Post Votes (To track who voted what and prevent double voting)
CREATE TABLE public.post_votes (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    post_id UUID REFERENCES public.posts(id) ON DELETE CASCADE NOT NULL,
    user_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    vote_type SMALLINT NOT NULL CHECK (vote_type IN (1, -1)), -- 1 for upvote, -1 for downvote
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE(post_id, user_id)
);

ALTER TABLE public.post_votes ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Votes are viewable by everyone." ON public.post_votes FOR SELECT USING (true);
CREATE POLICY "Users can insert/update their own votes." ON public.post_votes FOR ALL USING (auth.uid() = user_id);

-- Comments
CREATE TABLE public.comments (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    post_id UUID REFERENCES public.posts(id) ON DELETE CASCADE NOT NULL,
    author_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    content TEXT NOT NULL,
    edit_history JSONB DEFAULT '[]'::jsonb NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

ALTER TABLE public.comments ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Comments are viewable by everyone." ON public.comments FOR SELECT USING (true);
CREATE POLICY "Authenticated users can create comments." ON public.comments FOR INSERT WITH CHECK (auth.role() = 'authenticated');
CREATE POLICY "Authors can update their own comments." ON public.comments FOR UPDATE USING (auth.uid() = author_id);
CREATE POLICY "Authors can delete their own comments." ON public.comments FOR DELETE USING (auth.uid() = author_id);

-- 6. RPCs for Voting
CREATE OR REPLACE FUNCTION handle_vote(p_post_id UUID, p_user_id UUID, p_vote_type SMALLINT)
RETURNS void AS $$
DECLARE
    v_existing_vote SMALLINT;
BEGIN
    -- Check if vote exists
    SELECT vote_type INTO v_existing_vote
    FROM public.post_votes
    WHERE post_id = p_post_id AND user_id = p_user_id;

    IF v_existing_vote IS NOT NULL THEN
        IF v_existing_vote = p_vote_type THEN
            -- User is clicking the same vote button, so remove the vote (toggle off)
            DELETE FROM public.post_votes WHERE post_id = p_post_id AND user_id = p_user_id;
            
            IF p_vote_type = 1 THEN
                UPDATE public.posts SET upvotes = upvotes - 1 WHERE id = p_post_id;
            ELSE
                UPDATE public.posts SET downvotes = downvotes - 1 WHERE id = p_post_id;
            END IF;
        ELSE
            -- User is changing their vote (e.g., from down to up)
            UPDATE public.post_votes SET vote_type = p_vote_type WHERE post_id = p_post_id AND user_id = p_user_id;
            
            IF p_vote_type = 1 THEN
                UPDATE public.posts SET upvotes = upvotes + 1, downvotes = downvotes - 1 WHERE id = p_post_id;
            ELSE
                UPDATE public.posts SET downvotes = downvotes + 1, upvotes = upvotes - 1 WHERE id = p_post_id;
            END IF;
        END IF;
    ELSE
        -- New vote
        INSERT INTO public.post_votes (post_id, user_id, vote_type) VALUES (p_post_id, p_user_id, p_vote_type);
        
        IF p_vote_type = 1 THEN
            UPDATE public.posts SET upvotes = upvotes + 1 WHERE id = p_post_id;
        ELSE
            UPDATE public.posts SET downvotes = downvotes + 1 WHERE id = p_post_id;
        END IF;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 6.5 RPCs for Editing Posts & Comments
CREATE OR REPLACE FUNCTION rpc_edit_post(
    p_post_id UUID,
    p_title TEXT,
    p_content TEXT,
    p_user_id UUID
) RETURNS boolean AS $$
DECLARE
    v_old_title TEXT;
    v_old_content TEXT;
    v_old_updated_at TIMESTAMP WITH TIME ZONE;
    v_new_history_entry JSONB;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM public.posts WHERE id = p_post_id AND author_id = p_user_id) THEN
        RETURN false;
    END IF;

    SELECT title, content, updated_at INTO v_old_title, v_old_content, v_old_updated_at
    FROM public.posts
    WHERE id = p_post_id;

    v_new_history_entry := jsonb_build_object(
        'title', v_old_title,
        'content', v_old_content,
        'edited_at', v_old_updated_at
    );

    UPDATE public.posts
    SET title = p_title,
        content = p_content,
        updated_at = timezone('utc'::text, now()),
        edit_history = edit_history || jsonb_build_array(v_new_history_entry)
    WHERE id = p_post_id;

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

CREATE OR REPLACE FUNCTION rpc_edit_comment(
    p_comment_id UUID,
    p_content TEXT,
    p_user_id UUID
) RETURNS boolean AS $$
DECLARE
    v_old_content TEXT;
    v_old_updated_at TIMESTAMP WITH TIME ZONE;
    v_new_history_entry JSONB;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM public.comments WHERE id = p_comment_id AND author_id = p_user_id) THEN
        RETURN false;
    END IF;

    SELECT content, updated_at INTO v_old_content, v_old_updated_at
    FROM public.comments
    WHERE id = p_comment_id;

    v_new_history_entry := jsonb_build_object(
        'content', v_old_content,
        'edited_at', v_old_updated_at
    );

    UPDATE public.comments
    SET content = p_content,
        updated_at = timezone('utc'::text, now()),
        edit_history = edit_history || jsonb_build_array(v_new_history_entry)
    WHERE id = p_comment_id;

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 7. Insert Default Communities
INSERT INTO public.communities (name, description, is_general) 
VALUES ('General BRACU', 'The main hub for all non-specific BRACU discussions, memes, and announcements.', true)
ON CONFLICT (name) DO NOTHING;

INSERT INTO public.communities (name, description, is_general) 
VALUES ('Faculty & Course Reviews', 'Share your experiences and honest reviews about specific courses and faculty members.', true)
ON CONFLICT (name) DO NOTHING;

-- 8. Study Materials Storage & Handlers

-- Create Storage Bucket
INSERT INTO storage.buckets (id, name, public) 
VALUES ('study_materials', 'study_materials', true)
ON CONFLICT (id) DO NOTHING;

-- Storage Policies
CREATE POLICY "Public access to study materials" 
ON storage.objects FOR SELECT 
USING (bucket_id = 'study_materials');

CREATE POLICY "Authenticated users can upload study materials" 
ON storage.objects FOR INSERT 
WITH CHECK (bucket_id = 'study_materials' AND auth.role() = 'authenticated');

-- RPC for secure upload transaction
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

-- RPC for secure download transaction (costs 1 credit / download)
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

-- RPC for refunding a download due to a broken Google Drive link
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

-- 8.5 Material Reports Table (Not Accessible Links)
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

-- 8.6 Material Requests Table
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
CREATE POLICY "Users can delete their own requests" ON public.material_requests FOR DELETE USING (auth.uid() = requester_id);
-- Note: Fulfillment status updates will be handled via RPC to prevent cheating


-- 9. Ride Sharing Tables

CREATE TABLE public.rides (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    driver_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    vehicle_type TEXT NOT NULL CHECK (vehicle_type IN ('Rickshaw', 'Bike', 'Car/CNG')),
    start_location TEXT NOT NULL,
    end_location TEXT NOT NULL,
    departure_time TIMESTAMP WITH TIME ZONE NOT NULL,
    total_seats INTEGER NOT NULL,
    available_seats INTEGER NOT NULL,
    price_per_seat INTEGER DEFAULT 0 NOT NULL,
    status TEXT DEFAULT 'open' CHECK (status IN ('open', 'full', 'completed', 'cancelled')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

CREATE TABLE public.ride_requests (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    ride_id UUID REFERENCES public.rides(id) ON DELETE CASCADE NOT NULL,
    passenger_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    status TEXT DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected', 'cancelled')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE(ride_id, passenger_id) -- A user can only request a specific ride once
);

CREATE TABLE public.ride_reviews (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    ride_id UUID REFERENCES public.rides(id) ON DELETE CASCADE NOT NULL,
    reviewer_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    reviewee_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5) NOT NULL,
    comment TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE(ride_id, reviewer_id, reviewee_id) -- One review per person per ride
);

-- RLS for Rides
ALTER TABLE public.rides ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Rides are viewable by everyone" ON public.rides FOR SELECT USING (true);
CREATE POLICY "Users can create rides" ON public.rides FOR INSERT WITH CHECK (auth.uid() = driver_id);
CREATE POLICY "Drivers can update their rides" ON public.rides FOR UPDATE USING (auth.uid() = driver_id);

-- RLS for Ride Requests
ALTER TABLE public.ride_requests ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Users can view their requests or requests for their rides" 
ON public.ride_requests FOR SELECT 
USING (auth.uid() = passenger_id OR auth.uid() IN (SELECT driver_id FROM public.rides WHERE rides.id = ride_requests.ride_id));

CREATE POLICY "Users can create requests" 
ON public.ride_requests FOR INSERT 
WITH CHECK (auth.uid() = passenger_id);

CREATE POLICY "Drivers can update requests for their rides" 
ON public.ride_requests FOR UPDATE 
USING (auth.uid() IN (SELECT driver_id FROM public.rides WHERE rides.id = ride_requests.ride_id));

-- RLS for Ride Reviews
ALTER TABLE public.ride_reviews ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Reviews are viewable by everyone" ON public.ride_reviews FOR SELECT USING (true);
CREATE POLICY "Users can create reviews" ON public.ride_reviews FOR INSERT WITH CHECK (auth.uid() = reviewer_id);

-- RPC for accepting a ride request
CREATE OR REPLACE FUNCTION rpc_accept_ride_request(p_request_id UUID) RETURNS boolean AS $$
DECLARE
    v_ride_id UUID;
    v_available_seats INTEGER;
BEGIN
    -- Get ride info
    SELECT ride_id INTO v_ride_id FROM public.ride_requests WHERE id = p_request_id;
    
    -- Check available seats
    SELECT available_seats INTO v_available_seats FROM public.rides WHERE id = v_ride_id FOR UPDATE;
    
    IF v_available_seats > 0 THEN
        -- Accept request
        UPDATE public.ride_requests SET status = 'accepted' WHERE id = p_request_id;
        
        -- Decrement seats
        UPDATE public.rides SET available_seats = available_seats - 1 WHERE id = v_ride_id;
        
        -- If full, update status
        IF v_available_seats - 1 = 0 THEN
            UPDATE public.rides SET status = 'full' WHERE id = v_ride_id;
        END IF;
        
        RETURN true;
    ELSE
        RETURN false;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- RPC for rejecting a ride request
CREATE OR REPLACE FUNCTION rpc_reject_ride_request(p_request_id UUID) RETURNS void AS $$
BEGIN
    UPDATE public.ride_requests SET status = 'rejected' WHERE id = p_request_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
