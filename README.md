# Configuration

### composer.json

```
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/saloxiddinnuridinov/my-auth-package"
        }
    ],
```
```
"my/auth": "dev-main"
```

# Composer update

```
composer update
```

# app.php
### config/app.php providers => [
    ....
    My\Auth\MyAuthServiceProvider::class,
]
### kiritish kerak!

```
My\Auth\MyAuthServiceProvider::class,
```

