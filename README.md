# Search Lucene

[![Build Status](https://secure.travis-ci.org/owncloud/search_lucene.png)](http://travis-ci.org/owncloud/search_lucene)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/owncloud/search_lucene/badges/quality-score.png)](https://scrutinizer-ci.com/g/owncloud/search_lucene/)
[![Scrutinizer Code Coverage](https://scrutinizer-ci.com/g/owncloud/search_lucene/badges/coverage.png)](https://scrutinizer-ci.com/g/owncloud/search_lucene/)

The Search Lucene app adds a full text search for files stored in ownCloud. It is based on
[Zend Search Lucene](http://framework.zend.com/manual/1.12/en/zend.search.lucene.html) and
can index content from plain text, .docx, .xlsx, .pptx, .odt, .ods and .pdf files. The source
code is [available on GitHub](https://github.com/owncloud/search_lucene)

# Maintainers

Maintainers wanted for additional features!

* [Jörn Friedrich Dreyer](https://github.com/butonic)

# Known limitations

* Does not work with Encryption: the background indexing process does not have access to the
  key needed to decrypt files when the user is not logged in.
* Does not index files in external storage. For performance reasons.
* Not all PDF versions can be indexed. The text extraction used for it is incompatible with newer PDF versions.
