-- Add pricing_type column to rides table
ALTER TABLE rides ADD COLUMN IF NOT EXISTS pricing_type text DEFAULT 'exact' CHECK (pricing_type IN ('exact', 'free', 'split'));

-- RPC to cancel an entire ride (Driver cancels)
-- Penalizes the driver's trust score by 5 points.
-- Updates the main ride to 'cancelled' and all associated requests to 'cancelled_by_driver'.
CREATE OR REPLACE FUNCTION rpc_cancel_ride(p_ride_id UUID, p_driver_id UUID)
RETURNS void AS $$
BEGIN
    -- Verify ownership and current status
    IF NOT EXISTS (SELECT 1 FROM rides WHERE id = p_ride_id AND driver_id = p_driver_id AND status = 'open') THEN
        RAISE EXCEPTION 'Ride not found, already cancelled/completed, or unauthorized.';
    END IF;

    -- Update the ride status
    UPDATE rides
    SET status = 'cancelled'
    WHERE id = p_ride_id;

    -- Update all pending/accepted requests for this ride
    UPDATE ride_requests
    SET status = 'cancelled_by_driver'
    WHERE ride_id = p_ride_id AND status IN ('pending', 'accepted');

    -- Penalize the driver (-5 trust score)
    UPDATE profiles
    SET trust_score = trust_score - 5
    WHERE id = p_driver_id;
END;
$$ LANGUAGE plpgsql;

-- RPC to cancel a specific ride request (Passenger cancels)
-- If the request was 'accepted', frees up a seat and penalizes the passenger's trust score by 3 points.
-- If the request was 'pending', simply cancels without penalty.
CREATE OR REPLACE FUNCTION rpc_cancel_ride_request(p_request_id UUID, p_passenger_id UUID)
RETURNS void AS $$
DECLARE
    v_ride_id UUID;
    v_status text;
BEGIN
    -- Get current request info
    SELECT ride_id, status INTO v_ride_id, v_status
    FROM ride_requests
    WHERE id = p_request_id AND passenger_id = p_passenger_id;

    IF v_ride_id IS NULL THEN
        RAISE EXCEPTION 'Request not found or unauthorized.';
    END IF;

    IF v_status NOT IN ('pending', 'accepted') THEN
        RAISE EXCEPTION 'Request is already processed or cancelled.';
    END IF;

    -- Update request status
    UPDATE ride_requests
    SET status = 'cancelled_by_passenger'
    WHERE id = p_request_id;

    -- If accepted, restore available seats and apply penalty
    IF v_status = 'accepted' THEN
        -- Restore seating
        UPDATE rides
        SET available_seats = available_seats + 1
        WHERE id = v_ride_id;

        -- Penalize the passenger (-3 trust score for cancelling late)
        UPDATE profiles
        SET trust_score = trust_score - 3
        WHERE id = p_passenger_id;
    END IF;
END;
$$ LANGUAGE plpgsql;
