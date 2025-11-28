# Benefits

- **Events**: Decouples code for maintainability; easy to add/remove handlers.
- **Rooms**: Reduces bandwidth by targeting subsets (e.g., 1-1 chat without global flood).
- **Encryption**: Protects data in transit; AES-256 is FIPS-compliant for security.
- **Middleware**: Custom auth/logic without core changes; runs once per connect.
- **DB Integration**: Persist data for offline users; query history on join.
- **Auto-Reconnect**: Improves UX in unreliable networks; exponential backoff prevents spam.
- **PHPDoc**: VS Code hovers show full docs/params for faster dev.