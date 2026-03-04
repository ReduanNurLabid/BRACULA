-- 1. Add edit_history column to posts and comments
ALTER TABLE public.posts ADD COLUMN IF NOT EXISTS edit_history JSONB DEFAULT '[]'::jsonb NOT NULL;
ALTER TABLE public.comments ADD COLUMN IF NOT EXISTS edit_history JSONB DEFAULT '[]'::jsonb NOT NULL;

-- 2. Add Delete policy to material_requests
CREATE POLICY "Users can delete their own requests" 
ON public.material_requests FOR DELETE 
USING (auth.uid() = requester_id);

-- 3. Create RPC for Editing Posts
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
    -- Check ownership
    IF NOT EXISTS (SELECT 1 FROM public.posts WHERE id = p_post_id AND author_id = p_user_id) THEN
        RETURN false;
    END IF;

    -- Get current state
    SELECT title, content, updated_at INTO v_old_title, v_old_content, v_old_updated_at
    FROM public.posts
    WHERE id = p_post_id;

    -- Create history entry
    v_new_history_entry := jsonb_build_object(
        'title', v_old_title,
        'content', v_old_content,
        'edited_at', v_old_updated_at
    );

    -- Update row
    UPDATE public.posts
    SET title = p_title,
        content = p_content,
        updated_at = timezone('utc'::text, now()),
        edit_history = edit_history || jsonb_build_array(v_new_history_entry)
    WHERE id = p_post_id;

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 4. Create RPC for Editing Comments
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
    -- Check ownership
    IF NOT EXISTS (SELECT 1 FROM public.comments WHERE id = p_comment_id AND author_id = p_user_id) THEN
        RETURN false;
    END IF;

    -- Get current state
    SELECT content, updated_at INTO v_old_content, v_old_updated_at
    FROM public.comments
    WHERE id = p_comment_id;

    -- Create history entry
    v_new_history_entry := jsonb_build_object(
        'content', v_old_content,
        'edited_at', v_old_updated_at
    );

    -- Update row
    UPDATE public.comments
    SET content = p_content,
        updated_at = timezone('utc'::text, now()),
        edit_history = edit_history || jsonb_build_array(v_new_history_entry)
    WHERE id = p_comment_id;

    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
