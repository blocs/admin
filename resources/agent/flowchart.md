```mermaid
flowchart LR
    request(request) --> Home([redirectHome])
        request --> Profile([redirectProfile])
    request --> auth(auth)
        auth --> TextLoginEmail([askTextLoginEmail])
        auth --> TextLoginPassword([askTextLoginPassword])
        auth --> Login[[tyrLogin
        $email,$password]]
        TextLoginEmail --> Login
        TextLoginPassword --> Login
        auth --> Logout[[tyrLogout]]
    request --> user(user)
        user --> User[[redirectUser
        $query]]
        user --> TextUserCreateEmail([askTextUserCreateEmail])
        user --> UserCreate[[redirectUserCreate
        $email]]
        TextUserCreateEmail --> UserCreate
        user --> UserDestroy[[redirectUserDestroy
        $query]]
```
