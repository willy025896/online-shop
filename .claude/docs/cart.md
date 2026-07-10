# Cart

Cart supports both guests and authenticated users. `CartService` identifies a cart by `user_id` for authenticated users and `session_id` for guests. On login, `CartService::mergeGuestCart()` merges the guest cart into the user's cart. Policies in `app/Policies/` govern seller/admin resource authorization.
