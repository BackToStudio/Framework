# Session management trade-offs


`SessionManager` limits concurrent sessions per user (default: 1). When a new session exceeds the limit, the oldest sessions are destroyed. This prevents credential sharing and limits the blast radius of a compromised password. The implementation runs two passes to handle race conditions where a new session is created between reading and deleting existing ones.
