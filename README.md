# Hashid Field

Enables you to add a unique and read-only hash to an entry, generated using the entry ID. Ideal for using as an obfuscated URL slug or non-sequential ID. This extension uses the [Hashids](http://www.hashids.org/php/) library.

[![Code Climate](https://codeclimate.com/github/nathanhornby/hashid_field.png)](https://codeclimate.com/github/nathanhornby/hashid_field)

## Installation and usage

1. Upload the `hashid_field` folder to your Symphony `/extensions` folder.
2. Enable it by selecting **Hashid Field** in the Extensions list, choose *Enable* from the "With Selected…" dropdown, then click *Apply*.
3. Add to any section where you need a hash!

The field requires no interaction from the end-user as it automatically populates when the entry is created.

Hashes are regenereated when a pre-existing entry is saved, so if you change the salt or hash length and update an entry, its hash will change (otherwise it will remain the same). So it's generally advised to set-and-forget unless that's your intention. This is especially important if you plan on using the hashes for URL slugs. The field will warn the user if the hash is going to regenerate.

You can also regenerate Hashid fields via the 'With selected' toggle on the publish page. This works under the same conditions as above.

**The minimum hash length and the salt can be set for each instance of the hashid field, and you can set defaults in your Symphony preferences.**

### Hash salt

Your sitename is used as the default salt when the extension is installed. If your hashes are to be used for any obfuscation purposes then ensure that your salt is as random/chaotic as possible, as two sites using the same salt will share hash values for the same corresponding entry IDs.

### Hash length

The default hash length is set to `5` when the extension is installed. This is sufficient for the vast majority of cases, and you're highly unlikely to hit the unique limit in a single section. The field supports up to 32 characters, lengths set above `32` will be truncated.

## How to's

### Using the hash for URL obfuscation

This ones easy; just set a 'hashid' parameter on your page and filter by your hashid field in the datasource.

### Using the hash for ID obfuscation in events

There are a few ways of using the hashid with events, but my preferred method is to use the hash in place of the entry ID. i.e.

`<input type="text" name="id" value="{section/entry/hashid_field}" />`

Then you just have to swap this out for your entry ID in your `__trigger` function in the relevant event:

```
protected function __trigger()
{
    // If the ID isn't a number then it's a hash, so convert it to the entry ID
    if( isset($_POST['id']) && !is_numeric($_POST['id']) )
    {
        require_once EXTENSIONS . '/hashid_field/vendor/autoload.php';

        $hash = new \Hashids\Hashids( 'TheSaltForThisField' , 6 );
        $decode_array = $hash->decode($_POST['id']);
        $_POST['id'] = $decode_array[0];
    };

    return $result;
}
```

Where `'TheSaltForThisField'` and `6` are your hash salt and hash length. These need to be manually set in the event and are static values.
