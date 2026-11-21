<?php

return [

'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'], // Batasi ke domain frontend saat production nanti
'allowed_headers' => ['*'],

];
