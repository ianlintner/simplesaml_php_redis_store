What is it?
**Metadata Source Class for SimpleSamlPHP..**

so you can back your data with a redis cache which maybe preferable if your file system is slow or you use Redis for Session Store and want consistency. Since I assume redis is running in volitle mode and not persisting data on clear/reset this is backed by a FlatFile to initialize.


**Is it necessary?** Maybe because Redis... probably but could be extended for a persistent store or dynamic runtime changes to meta data...

**How I use it:**
1. Put in simplesaml lib/SIMPLESAML/Metadata folder
2. Redis Config for Session Store is Required or at least Redis Connection set up you may need more options depending on how you redis.

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

**Add the metadata sources to your config**
```PHP
'metadata.sources' => [
    ['type' => 'redis'],
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

You are modifying the source at this point so I reccommend you symlink this file, but if you perform version upgrades there really isn't an automatic upgrade path. Possibly patch file bash/git shennanigans with a build server could solve it. But since the composer updates core changes are not as painful.

This is provided as is and is a proof of concept for you to modify as needed. License Matches SimpleSamlPHP GPL v2.1
