How I use it:
1. Put in simplesaml lib/SIMPLESAML/Metadata folder
2. Redis Config for Session Store is Required

```PHP
Config your session store redis cache
function redis_session_store(array $config, array $local_options) {
  if (!empty($local_options['redis_password'])) {
    $config['store.redis.password '] = $local_options['redis_password'];
  }
  $config['store.redis.port'] = empty($local_options['redis_port']) ? '6379' : $local_options['redis_port'];
  $config['store.redis.host'] = empty($local_options['redis_host']) ? 'localhost' : $local_options['redis_host'];
  return $config;
}
```

Add the metadata sources
```PHP
'metadata.sources' => [
    ['type' => 'flatfile'],
    ['type' => 'pdo'],
],
```

3. Update lib/SIMPLESAML/Metadata/MetaDataStorageSource.php

```php
//See
public static function getSource($sourceConfig)
```
Add the line
```php
            case 'redis':
                return new SimpleSAML_Metadata_MetaDataStorageHandlerRedisCache($sourceConfig);
```


This is provided as is and is a proof of concept for you to modify as needed. License Matches SimpleSamlPHP GPL v2.1
