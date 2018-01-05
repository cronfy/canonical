# Redirect to canonical domain name/scheme

Provides framework agnostic redirects to canonical host/scheme 
without ```.htaccess```.

Example:

```php
$canonical = new \cronfy\canonical\Request();
$canonical->setCanonicalHost('example.com');
$canonical->setCanonicalScheme('https');
$canonical->redirectToCanonical();
```

If requested host/scheme does not match canonical ones, user will be redirected:

```
http://www.example.com/contacts/ => https://example.com/contacts/
```

## Installation

```bash
composer require cronfy/canonical dev-master
```

## Related

>> SO [Как сделать всё и сразу в mod_rewrite?](https://ru.stackoverflow.com/questions/542869/%D0%9A%D0%B0%D0%BA-%D1%81%D0%B4%D0%B5%D0%BB%D0%B0%D1%82%D1%8C-%D0%B2%D1%81%D1%91-%D0%B8-%D1%81%D1%80%D0%B0%D0%B7%D1%83-%D0%B2-mod-rewrite)
  
> [Вы идёте неправильным путём](https://ru.stackoverflow.com/a/542870/200260)