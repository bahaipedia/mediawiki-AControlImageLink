# What it does

This extension prevents `[[File:Something.pdf|thumb]]` from rendering on the pages if all of the following is true:
1) `pdf` is listed in `$wgAControlImageLinkRestrictedExtensions` array:
```php
$wgAControlImageLinkRestrictedExtensions = [ 'pdf' ],
```
2) `File:Something.pdf` has `<accesscontrol>` tag,
3) Article that includes `[[File:]]` syntax either doesn't have `<accesscontrol>` tag,
or its `<accesscontrol>` tag is different from `<accesscontrol>` on `File:Something.pdf`.

If thumbnailing was prevented, an image link will be shown instead of thumbnail.

# Why is this needed

For example, a wiki might want to display a page from PDF book,
but also show some disclaimers or something on the same page as PDF thumbnail. 

This extension can be used to prevent including this PDF book to pages which are not marked
as pages where this PDF book can be included.

(you can allow it by adding `<accesscontrol>` tag to `File:Something.pdf` and article)
