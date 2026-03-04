-- 1. Update Profiles Table (Safely)
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'profiles' AND column_name = 'shoutbox_username') THEN
        ALTER TABLE public.profiles ADD COLUMN shoutbox_username TEXT UNIQUE;
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'profiles' AND column_name = 'shoutbox_username_updated_at') THEN
        ALTER TABLE public.profiles ADD COLUMN shoutbox_username_updated_at TIMESTAMP WITH TIME ZONE;
    END IF;
END $$;

-- 2. Create Shoutbox Messages Table (Safely)
CREATE TABLE IF NOT EXISTS public.shoutbox_messages (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    author_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    shoutbox_username TEXT NOT NULL, -- Storing the username at the time of send for simplicity
    content TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Note: In Supabase, you must enable Realtime for a specific table manually in the Dashboard
-- or via SQL:
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_publication_tables 
        WHERE pubname = 'supabase_realtime' AND schemaname = 'public' AND tablename = 'shoutbox_messages'
    ) THEN
        EXECUTE 'ALTER PUBLICATION supabase_realtime ADD TABLE public.shoutbox_messages';
    END IF;
END $$;

-- 3. Enable RLS and Policies for Messages (Safely)
ALTER TABLE public.shoutbox_messages ENABLE ROW LEVEL SECURITY;

DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE tablename = 'shoutbox_messages' 
        AND policyname = 'Shoutbox messages are viewable for 6 hours'
    ) THEN
        CREATE POLICY "Shoutbox messages are viewable for 6 hours" 
        ON public.shoutbox_messages FOR SELECT 
        USING (created_at > timezone('utc'::text, now()) - INTERVAL '6 hours');
    END IF;
END $$;

-- (We handle INSERTS strictly via RPC to enforce rate limits)

-- 4. RPC for setting a Shoutbox Username with a 7-day cooldown
CREATE OR REPLACE FUNCTION rpc_set_shoutbox_username(p_user_id UUID, p_username TEXT)
RETURNS void AS $$
DECLARE
    v_last_updated TIMESTAMP WITH TIME ZONE;
    v_days_since_update INTEGER;
BEGIN
    -- Check if username is already taken by someone else (case-insensitive)
    IF EXISTS (SELECT 1 FROM public.profiles WHERE shoutbox_username ILIKE p_username AND id != p_user_id) THEN
        RAISE EXCEPTION 'Username already taken. Please choose another one.';
    END IF;

    -- Get user's last update time
    SELECT shoutbox_username_updated_at INTO v_last_updated 
    FROM public.profiles 
    WHERE id = p_user_id;

    -- If updated before, check if 7 days have passed
    IF v_last_updated IS NOT NULL THEN
        -- Calculate difference in days
        v_days_since_update := EXTRACT(DAY FROM (timezone('utc'::text, now()) - v_last_updated));
        
        IF v_days_since_update < 7 THEN
            RAISE EXCEPTION 'You can only change your shoutbox username every 7 days. Please wait % more days.', (7 - v_days_since_update);
        END IF;
    END IF;

    -- Set the new username
    UPDATE public.profiles
    SET shoutbox_username = p_username,
        shoutbox_username_updated_at = timezone('utc'::text, now())
    WHERE id = p_user_id;

END;
$$ LANGUAGE plpgsql SECURITY DEFINER;


-- 5. RPC for sending a shout with a 30-second rate limit
CREATE OR REPLACE FUNCTION rpc_send_shout(p_user_id UUID, p_content TEXT)
RETURNS void AS $$
DECLARE
    v_last_message_time TIMESTAMP WITH TIME ZONE;
    v_seconds_since_last INTEGER;
    v_username TEXT;
BEGIN
    -- Get the user's current shoutbox username
    SELECT shoutbox_username INTO v_username
    FROM public.profiles
    WHERE id = p_user_id;

    IF v_username IS NULL THEN
        RAISE EXCEPTION 'You must set a shoutbox username first';
    END IF;

    -- Get the timestamp of their last message
    SELECT created_at INTO v_last_message_time
    FROM public.shoutbox_messages
    WHERE author_id = p_user_id
    ORDER BY created_at DESC
    LIMIT 1;

    -- If they have sent a message before, check the 30 sec limit
    IF v_last_message_time IS NOT NULL THEN
        v_seconds_since_last := EXTRACT(EPOCH FROM (timezone('utc'::text, now()) - v_last_message_time));
        
        IF v_seconds_since_last < 30 THEN
            RAISE EXCEPTION 'Rate limit exceeded. Please wait % seconds.', (30 - v_seconds_since_last);
        END IF;
    END IF;

    -- Insert the message
    INSERT INTO public.shoutbox_messages (author_id, shoutbox_username, content)
    VALUES (p_user_id, v_username, p_content);

END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
