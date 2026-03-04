-- 1. Create Post Reports Table (Safely)
CREATE TABLE IF NOT EXISTS public.post_reports (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    post_id UUID REFERENCES public.posts(id) ON DELETE CASCADE NOT NULL,
    reporter_id UUID REFERENCES public.profiles(id) ON DELETE CASCADE NOT NULL,
    reason TEXT,
    status TEXT DEFAULT 'pending'::text NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2. Enable RLS
ALTER TABLE public.post_reports ENABLE ROW LEVEL SECURITY;

-- 3. RLS Policies
-- Users can insert their own reports
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE tablename = 'post_reports' 
        AND policyname = 'Users can insert own reports'
    ) THEN
        CREATE POLICY "Users can insert own reports" 
        ON public.post_reports FOR INSERT 
        WITH CHECK (auth.uid() = reporter_id);
    END IF;
END $$;

-- Admins can view all reports
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE tablename = 'post_reports' 
        AND policyname = 'Admins can view all reports'
    ) THEN
        CREATE POLICY "Admins can view all reports" 
        ON public.post_reports FOR SELECT 
        USING (
            EXISTS (
                SELECT 1
                FROM public.profiles
                WHERE (profiles.id = auth.uid()) 
                AND (profiles.email IN ('reduan.nur.labid@g.bracu.ac.bd', 'nurreduan.pp@gmail.com'))
            )
        );
    END IF;
END $$;

-- Admins can update reports (e.g., dismiss them)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE tablename = 'post_reports' 
        AND policyname = 'Admins can update reports'
    ) THEN
        CREATE POLICY "Admins can update reports" 
        ON public.post_reports FOR UPDATE 
        USING (
            EXISTS (
                SELECT 1
                FROM public.profiles
                WHERE (profiles.id = auth.uid()) 
                AND (profiles.email IN ('reduan.nur.labid@g.bracu.ac.bd', 'nurreduan.pp@gmail.com'))
            )
        );
    END IF;
END $$;
