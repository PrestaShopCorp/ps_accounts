# [ACCOUNT-1413]

- OwnerUid (User Token)
  - route admin ajax
  - auto-refresh
  - OR trigger popup connect 
    - trigger User & Shop token refresh
    - can also solve token expiration issue
- ~~Accounts-ui Session~~
  - route admin ajax
  - auto-refresh
  - trigger Popup
  - route admin ajax save token
- ~~BO connect~~
  - route admin ajax
  - auto refresh
  - redirect login (MBO side?)

---

1. [ ] implement an admin ajax route to geOrRefreshUserToken
2. [ ] implement a hook that triggers redirect login 
2. [ ] implement a hook to trigger re-connexion popup
   1. [ ] re-connexion should triger refresh shop tokens from accounts-api

