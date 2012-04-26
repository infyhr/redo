## What is it?
Redo is a simple micro-php framework built for designing quick web apps.

```php
<?php
require_once './redo.php';
redo::initialize();


redo::route(array(
    '/' =>  'index'
));

function index() { echo 'Hello World.'; }

redo::run();
```

## Requirements
- .htaccess support
- PHP 5.3+

## License
redo is licensed under MIT.

## Documentation
...is on the wiki page over [here](https://github.com/infyhr/redo/wiki/Documentation)