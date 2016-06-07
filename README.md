# MediaList Plugin for DokuWiki

This plugin shows a list of media files for a given wikipage which are
either linked in the page itself or belong to its namespace.
Note, the plugin is aware of your ACLs.

## Usage
To list the media files linked in the given page use:

    {{medialist>wikipage}}

To list the media files linked in the current page use:

    {{medialist>@PAGE@}}

To list the media files contained in the current namespace use:

    {{medialist>@NAMESPACE@}}

To list both, media files linked in the current page and contained in the current namespace use:

    {{medialist>@ALL@}}

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

