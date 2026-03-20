# Why generic login error messages?

`LoginHardening` replaces WordPress's default login errors with a generic message. WordPress normally says "The password you entered for the username X is incorrect," which confirms the username exists. The bundle returns the same message regardless of whether the username or password was wrong, preventing username enumeration through the login form.
