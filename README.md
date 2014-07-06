# Hash Field

Enables you to add a unique and read-only hash to an entry, generated using the entry ID. Ideal for using as an obfuscated URL slug or non-sequential ID. This extension uses the Hashids PHP library: http://www.hashids.org/php/.

The minimum hash length and the salt can be set in your Symphony preferences. Hashes are re-genereated when a pre-existing entry is saved, so if you change the salt or hash length and update an entry, its hash will change. So it's generally advised to set these when first installing the extension and avoid changing them unless that's your intention. This is especially important if you plan on using the hashes for URL slugs.

Your sitename is used as the default salt when the extension is installed. If your hashes are to be used for any obfuscation purposes where uniqueness is important then ensure your salt is as random as possible before creating any entries, as two sites using the same salt will share hash values for the same corresponding entry IDs.

## Installation and usage
 
1. Upload the `/hash_field` folder to your Symphony `/extensions` folder
2. Enable it by selecting "Hash Field" in the list, choose Enable from the with-selected menu, then click Apply
3. Add to any section where you need a hash!
