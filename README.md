# H5P Google Tag Manager

This module is based on
[Google Tag Manager](https://www.drupal.org/project/google_tag) implementation
and uses that as a base. This means that all of the configuration, checks and
changes would apply to the H5P embed view.

The only purpose of this module is to add Google Tag Manager integration into
the view used for external embedding of content. It uses the same generated JS
file as the original module does.

**NB! Tis branch works with version 8.x-1.x.**

## NB! Quirks and fixed

1. The H5P module codebase does not allow inclusion of the HTML into the view.
Current implementation is also missing code for additional_embed_head_tags to be
properly allowed and corresponding hook triggered. Those changes would need to
be patched in.

This should add add all the missing hooks:
https://github.com/pjotrsavitski/h5p/commit/1ec79a6d9904d58249d0bbfe06ab1ea366993569.diff

This one should add the new hook to allow HTML to be added:
https://github.com/pjotrsavitski/h5p/commit/2009dfdb2f545ea9108c1e75b57bd0d4435158ac.diff

The second change is not strictly required and JS part should function well
enough even without it.
