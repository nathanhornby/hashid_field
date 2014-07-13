# Hashid Field

Enables you to add a unique and read-only hash to an entry, generated using the entry ID. Ideal for using as an obfuscated URL slug or non-sequential ID. This extension uses the [Hashids](http://www.hashids.org/php/) library.

[![Code Climate](https://codeclimate.com/github/nathanhornby/hashid_field.png)](https://codeclimate.com/github/nathanhornby/hashid_field)

## Installation and usage
 
1. Upload the `hashid_field` folder to your Symphony `/extensions` folder.
2. Enable it by selecting **Hashid Field** in the Extensions list, choose *Enable* from the "With Selected…" dropdown, then click *Apply*.
3. Add to any section where you need a hash!

This field requires no interaction from the end-user as it automatically populates when the entry is created.

The minimum hash length and the salt can be set for each instance of the hashid field, and you can set defaults in your Symphony preferences. The field will store 32 characters, if you set the minimum hash length to more than 32 characters it will be truncated.

Hashes are regenereated when a pre-existing entry is saved, so if you change the salt or hash length and update an entry, its hash will change (otherwise it will remain the same). So it's generally advised to set-and-forget unless that's your intention. This is especially important if you plan on using the hashes for URL slugs. The field will warn the user if the hash is going to regenerate.

You can also regenerate Hashid fields via the 'With selected' toggle on the publish page. This works under the same conditions as above.

Your sitename is used as the default salt when the extension is installed. If your hashes are to be used for any obfuscation purposes then ensure that your salt is as random/chaotic as possible, as two sites using the same salt will share hash values for the same corresponding entry IDs.

## Version history

### 1.2

- Notes here please

### 1.1

- Added publish toggle for regenerating Hashid's.
- Added hash salt and length as attributes to output XML for more advanced implementations that may want access to them.

### 1.0

- Changed hash length settings fields to only accept numbers.
- Checked compatability with older versions of Symphony, doesn't work pre-2.4.
- Changed layout of preferences.
- Titied up code and style inconsistencies, lots of comments added.

*Note: It's not recommended to update between pre v1.0 releases.*

### 0.5

- Removed optional flagging from field settings.

### 0.4

- UX improvements.

### 0.3

- Entries created with events now generate the hash.
- Fixed issue with displaying a new hash before the old hash has been replaced.

### 0.2

- Changed extension name from hash_field to hashid_field.
- Added field settings to allow for different salt's and lengths for each hashid field instance.
- Hashes are now created when the entry is created, not requiring a re-save.

### 0.1

- Extension created! Kind of works.
