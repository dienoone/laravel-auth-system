# Setting up OAuth Apps

Here's how to set up OAuth for each provider:

Google OAuth Setup:

1. Go to Google Cloud Console
2. Create a new project or select existing
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URIs:

-   http://localhost:8000/api/auth/google/callback
-   Your production URL

GitHub OAuth Setup:

1. Go to GitHub Settings > Developer settings > OAuth Apps
2. Click "New OAuth App"
3. Fill in the details:

-   Application name: Your App Name
-   Homepage URL: http://localhost:8000
-   Authorization callback URL: http://localhost:8000/api/auth/github/callback

Facebook OAuth Setup:

1. Go to Facebook Developers
2. Create a new app
3. Add Facebook Login product
4. Set Valid OAuth Redirect URIs:

-   http://localhost:8000/api/auth/facebook/callback

## Frontend Integration Example

For web applications:

```html
<!-- Login buttons -->
<a href="/api/auth/google">Login with Google</a>
<a href="/api/auth/github">Login with GitHub</a>
<a href="/api/auth/facebook">Login with Facebook</a>
```

For SPAs/Mobile apps (using the redirect URL)

```javascript
// Get redirect URL
const response = await fetch("/api/auth/google");
const data = await response.json();
window.location.href = data.data.redirect_url;

// Handle callback (in callback page)
const code = new URLSearchParams(window.location.search).get("code");
const response = await fetch("/api/auth/google/callback", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
    },
    body: JSON.stringify({ code }),
});
const authData = await response.json();
// Store token and redirect
```
