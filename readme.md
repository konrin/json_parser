# USER JSON parser

>Not for production!

### Usage

in code:

```php
var_dump(user_json_decode('{"key": "value"}'));
```

in cli:

```bash
$ php cli.php '{"key1":{"key2":[1,2,3]}' key1.key2.1

> 2
```

- arg1 - Json string
- arg2 (optional) - View path

## Testing

Testing is reduced to a simple comparison of the performance of the parsing of the native and user-defined functions.

```bash
$ php test.php 5 2

> json_decode 0.0001 сек 49.23 КБ
  user_json_decode 0.0051 сек 45.88 КБ
```

- arg1 - Rows count
- arg2 - Loop