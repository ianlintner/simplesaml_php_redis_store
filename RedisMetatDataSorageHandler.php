<?php


/**
 * RedisMetatDataSorageHandler.php 
 * Redis volitile storage of metadata backed by FlatFile as startup source.
 * @author Ian Lintner https://github.com/ianlintner
 *
 * Based on flat file source by
 *
 * @OriginalAuthor Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */
class SimpleSAML_Metadata_MetaDataStorageHandlerRedisCache extends SimpleSAML_Metadata_MetaDataStorageSource
{

    /**
     * This is the directory we will load metadata files from. The path will always end
     * with a '/'.
     *
     * @var string
     */
    private $directory;


    /**
     * This is an associative array which stores the different metadata sets we have loaded.
     *
     * @var array
     */
    private $cachedMetadata = array();

    private $redis;

    /**
     * This constructor initializes the flatfile metadata storage handler with the
     * specified configuration. The configuration is an associative array with the following
     * possible elements:
     * - 'directory': The directory we should load metadata from. The default directory is
     *                set in the 'metadatadir' configuration option in 'config.php'.
     *
     * @param array $config An associative array with the configuration for this handler.
     */
    protected function __construct($config) {
        assert(is_array($config));

        $this->redis = new \SimpleSAML\Store\Redis();

        // get the configuration
        $globalConfig = SimpleSAML_Configuration::getInstance();

        // find the path to the directory we should search for metadata in
        if (array_key_exists('directory', $config)) {
            $this->directory = $config['directory'];
        } else {
            $this->directory = $globalConfig->getString('metadatadir', 'metadata/');
        }

        /* Resolve this directory relative to the SimpleSAMLphp directory (unless it is
         * an absolute path).
         */
        $this->directory = $globalConfig->resolvePath($this->directory).'/';
    }


    /**
     * This function loads the given set of metadata from a file our metadata directory.
     * This function returns null if it is unable to locate the given set in the metadata directory.
     *
     * @param string $set The set of metadata we are loading.
     *
     * @return array An associative array with the metadata, or null if we are unable to load metadata from the given
     *     file.
     * @throws Exception If the metadata set cannot be loaded.
     */
    private function load($set)
    {
      if (empty($set)) {
        return NULL;
      }
      $metadatasetfile = $this->directory . $set . '.php';

      if (!file_exists($metadatasetfile)) {
        return NULL;
      }

      $metadata = array();

      include_once $metadatasetfile;

      if (!is_array($metadata)) {
        throw new Exception(
          'Could not load metadata set [' . $set . '] from file: ' . $metadatasetfile);
      }
      return $metadata;
    }


    /**
     * This function retrieves the given set of metadata. It will return an empty array if it is
     * unable to locate it.
     *
     * @param string $set The set of metadata we are retrieving.
     *
     * @return array An associative array with the metadata. Each element in the array is an entity, and the
     *         key is the entity id.
     */
    public function getMetadataSet($set)
    {
        $metadataSet = $this->redis->get(__CLASS__, "Metadata:set:$set");
        if(!empty($metadataSet)) {
          return $metadataSet;
        }

        if (array_key_exists($set, $this->cachedMetadata)) {
            return $this->cachedMetadata[$set];
        }

        $metadataSet = $this->load($set);
        if ($metadataSet === null) {
            $metadataSet = array();
        }

        // add the entity id of an entry to each entry in the metadata
        foreach ($metadataSet as $entityId => &$entry) {
            if (preg_match('/__DYNAMIC(:[0-9]+)?__/', $entityId)) {
                $entry['entityid'] = $this->generateDynamicHostedEntityID($set);
            } else {
                $entry['entityid'] = $entityId;
            }
        }
        $this->redis->set(__CLASS__, "Metadata:set:$set", $metadataSet);
        $this->cachedMetadata[$set] = $metadataSet;

        return $metadataSet;
    }


    private function generateDynamicHostedEntityID($set)
    {
        // get the configuration
        $baseurl = \SimpleSAML\Utils\HTTP::getBaseURL();

        if ($set === 'saml20-idp-hosted') {
            return $baseurl.'saml2/idp/metadata.php';
        } elseif ($set === 'shib13-idp-hosted') {
            return $baseurl.'shib13/idp/metadata.php';
        } elseif ($set === 'wsfed-sp-hosted') {
            return 'urn:federation:'.\SimpleSAML\Utils\HTTP::getSelfHost();
        } elseif ($set === 'adfs-idp-hosted') {
            return 'urn:federation:'.\SimpleSAML\Utils\HTTP::getSelfHost().':idp';
        } else {
            throw new Exception('Can not generate dynamic EntityID for metadata of this type: ['.$set.']');
        }
    }
}
