# Hashid Field

Enables you to add a unique and read-only hash to an entry, generated using the entry ID. Ideal for using as an obfuscated URL slug or non-sequential ID. This extension uses the Hashids PHP library: http://www.hashids.org/php/.

The minimum hash length and the salt can be set for each instance of the hashid field, and you can set defaults in your Symphony preferences. Hashes are re-genereated when a pre-existing entry is saved, so if you change the salt or hash length and update an entry, its hash will change. So it's generally advised to set-and-forget unless that's your intention. This is especially important if you plan on using the hashes for URL slugs.

Your sitename is used as the default salt when the extension is installed. If your hashes are to be used for any obfuscation purposes where uniqueness is important then ensure your salt is as random as possible, as two sites using the same salt will share hash values for the same corresponding entry IDs.

## Installation and usage
 
1. Upload the `/hashid_field` folder to your Symphony `/extensions` folder
2. Enable it by selecting "Hashid Field" in the list, choose Enable from the with-selected menu, then click Apply
3. Add to any section where you need a hash!

## Changelog

#0.2

- Changed extension name from hash_field to hashid_field.
- Added field settings to allow for different salt's and lengths for each hashid field instance.
- Hashes are now created when the entry is created, not requiring a re-save.

#0.1

- Extension created! Kind of works.
