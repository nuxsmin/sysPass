<?php

declare(strict_types=1);

return [
    'fakerphp/faker' => [
        'version' => '1.24.1',
        'patterns' => [
            '/^Since fakerphp\/faker [^:]+: Accessing property "[A-Za-z0-9_]+" is deprecated, use "[A-Za-z0-9_]+\(\)" instead\.$/',
            '/^Since fakerphp\/faker [^:]+: Provider is deprecated and will no longer be available in Faker \d+\. Please use a custom provider instead\.?$/',
        ],
    ],
    'klein/klein' => [
        'version' => '2.1.2',
        'patterns' => [
            '/^Return type of Klein\\\\DataCollection\\\\DataCollection::(?:getIterator|offsetExists|offsetGet|offsetSet|offsetUnset|count)\(.*\) should either be compatible with .*$/',
            '/^Klein\\\\[A-Za-z0-9_\\\\]+::[A-Za-z0-9_]+\(\): Implicitly marking parameter \$[A-Za-z0-9_]+ as nullable is deprecated, the explicit nullable type must be used instead$/',
        ],
    ],
];
