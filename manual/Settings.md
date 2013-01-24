#Settings

##Scrolls
###HERMES_SCROLL_LOCATION
The directory where Hermes will save the scrolls. Default: *./db/*
###HERMES_SCROLL_EXTENSION
Since **Hermes v0.3.0** the extension of scrolls are *.hermes* (used to be *.json-array.txt*). The file will contain a JSON database *[\n{},\n{},\n]* with the first *[* and last *]* ommitted.
###HERMES_SCROLL_SIZE_LIMIT
Sets the size to cut-off the scroll. The scroll would be slightly larger then its size limit because it will write a complete last record.
###HERMES_SCROLL_FORMAT and HERMES_SCROLL_FORMAT_DROP
Every month (with a '*Y-m[x]*'-like format) will start a new scroll, or a new set of scrolls. In the filename the *x=0* will be dropped.

##Records
###HERMES_ENCRYPTED_RECORD

##Identity
###HERMES_IDENTITY_BASE and HERMES_BASE_FIX_CHARACTER
