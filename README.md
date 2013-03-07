Hermes
======

*Webapplication Activity Log and Statistics*

"Hermes was a god of transitions and boundaries. (..) messenger of the gods, (..) conductor of souls into the afterlife." ([Wikipedia](http://en.wikipedia.org/wiki/Hermes)). In the same manner **Hermes** provides conduction of the activity on your webapplication to the records. Enabling to reconstruct *who is entering the underworld*.

See [documentation](https://github.com/sentfanwyaerda/Hermes/blob/master/manual/Hermes.md) for more information. Check the [changelog](https://github.com/sentfanwyaerda/Hermes/blob/master/manual/Changelog.md) for list of latest changes.

```php
Hermes( (string) $tag, (string) $value );
/*or*/ Hermes( (assigned array) $record );
```


```json
{"when": "1970-01-01T00:00:00+02:00", "identity": "9Qqsd~3C2ETuPuSx2OQ.s", "query": "p=1&lang=en"},
```
- this record can also be encrypted; the complete record, or only each value (except *when*).
