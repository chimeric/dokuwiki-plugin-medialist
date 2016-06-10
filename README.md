# MediaList Plugin for DokuWiki

This plugin shows a list of media files referred in a given wikipage or 
stored in a given namespace.
Note, the plugin is aware of your ACLs.

## Usage

Specify a scope parameter that defines the output of the list of media files. The scope can be a “page id” or a “namespace”. 

  1. **Page id** : look up media files linked in the page (i.e. curly brackets `{{...}}` in page text).
  2. **namespace** : look up media files stored the namespace. The parameter ends by `:` or `:*`, 

Some replacement patterns for namespace templates --- `@ID@`, `@NS@`, `@PAGE@` --- are available 
in order to specify the scope parameter.


To list media files linked in the specific page, use:

    {{medialist>wikipage}}

To list media files linked in the current page use:

    {{medialist>@ID@}} or {{medialist>@NS@:@PAGE@}}

To list media files stored in the current namespace use:

    {{medialist>@NS@:}}

To list media files stored in the current namespace and its sub-namesapces use:

    {{medialist>@NS@:*}}

More examples:

    {{medialist>ns1:ns2:*}}
    {{medialist>@NS@:start}}
    {{medialist>@ID@:}}

#### legacy syntax support

In ehe older release 2009-05-21 version, the scope parameter could be one of literal keywords, `@PAGE@`, `@NAMESPACE@` and `@ALL`. 
These literal keywords must be used as is, and are not kind of replacement pattens.

* `{{medialist>@PAGE@}}` shows files linked in the current page.
* `{{medialist>@NAMESPACE@}}` shows files stored in the current namespace and sub namecpaces.
* `{{medialist>@ALL@}}` shows all files when `@PAGE@` and `@NAMESPACE@` keywords given.

Legacy literal keywords should be corrected using replacement patterns:

    {{medialist>@PAGE@}} is same as {{medialist>@ID@}} 
    {{medialist>@NAMESPACE@}} is same as {{medialist>@NS@:}} 


----

See the MediList plugin page on DokuWiki.org for further information:

  * http://dokuwiki.org/plugin:medialist

(c) 2005 - 2009 by Michael Klier (chi@chimeric\.de)  
(c) 2016        by Satoshi Sahara (sahara\.satoshi@gmail\.com)  

This program is free software; you can redistribute it and/or modify  
it under the terms of the GNU General Public License as published by  
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,  
but WITHOUT ANY WARRANTY; without even the implied warranty of  
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
See the GNU General Public License for more details.

