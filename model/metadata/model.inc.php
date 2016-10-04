<?php

    class Model_metadata
    {

        public function __construct()
        {
            $this->silent = true;
            if ($this->silent) {
                //Reportinator::log("Model_metadata::__construct(): error_log has been silenced");
            }

            $key = 'cr_staff_metadataList';

            if (!($this->metadataList = unserialize(MC::get($key)))) {
                if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
                    $url  = Config::get('approot') . "/core/init/submit_types.json";
                    $json = file_get_contents($url);
                    $json = str_replace(array("\n", "\r"), "", $json);
                    $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $json);
                    $json = preg_replace('/(,)\s*}$/', '}', $json);

                    $temp = json_decode($json, true);
                    foreach ($temp as $type => $v) {
                        $fields = array();
                        foreach ($v as $field => $display) {
                            array_push($fields, $field);
                        }
                        $this->metadataList[$type] = $fields;
                    }
                    MC::set($key, serialize($this->metadataList),MC::getDuration('long'));
                }
            }
        }


        public function exists($processingType)
        {
            return isset($this->metadataList[$processingType]);
        }


        public function getMetadata($processingType)
        {

            $processingType = $this->_exists($processingType);

            return array(
                'fields' => $this->getMetadataFields($processingType),
                'titles' => $this->getMetadataTitles($processingType)
            );
        }


        public function getMetadataMap($engine = null, $processingType)
        {
            $processingType = $this->_exists($processingType);

            $engine = str_replace('-','_',$engine);

            if($engine !== 'cr_system'){
                Reportinator::log("Model_metadata::getMetadataMap(): engine => " . $engine . "     processingType => " . $processingType, null, $this->silent);
            }

            if ($engine === 'cr_system') {
                return array(
                    "item_title"       => "item_title",
                    "item_author"      => "item_author",
                    "journal_title"    => "journal_title",
                    "journal_volume"   => "journal_volume",
                    "journal_issue"    => "journal_issue",
                    "journal_month"    => "journal_month",
                    "journal_year"     => "journal_year",
                    "item_incpages"    => "item_incpages",
                    "item_isxn"        => "item_isxn",
                    "item_doi"         => "item_doi",
                    "item_edition"     => "item_edition",
                    "item_editor"      => "item_editor",
                    "item_publisher"   => "item_publisher",
                    "item_pubplace"    => "item_pubplace",
                    "item_pubdate"     => "item_pubdate",
                    "collection_title" => "collection_title",
                    "item_callnumber"  => "item_callnumber",
                    "item_uri"         => "item_uri"
                );
            } else if ($engine === 'development_one') {
                return array(
                    "item_title"       => "title",
                    "item_author"      => "author",
                    "journal_title"    => "journaltitle",
                    "journal_volume"   => "journalvolume",
                    "journal_issue"    => "journalissue",
                    "journal_month"    => "journalmonth",
                    "journal_year"     => "journalyear",
                    "item_incpages"    => "incpages",
                    "item_isxn"        => "isxn",
                    "item_doi"         => "doi",
                    "item_edition"     => "edition",
                    "item_editor"      => "editor",
                    "item_publisher"   => "publisher",
                    "item_pubplace"    => "pubplace",
                    "item_pubdate"     => "pubdate",
                    "collection_title" => "title",
                    "item_callnumber"  => "callnumber",
                    "item_uri"         => "uri"
                );
            } else if ($engine === 'ares') {
                if (in_array($processingType, array("electronic_article", "pdf_article"))) {
                    return array(
                        "item_title"     => "ArticleTitle",
                        "item_author"    => "Author",
                        "journal_title"  => "Title",
                        "journal_volume" => "Volume",
                        "journal_issue"  => "Issue",
                        "journal_month"  => "JournalMonth",
                        "journal_year"   => "JournalYear",
                        "item_incpages"  => "Pages",
                        "item_isxn"      => "ISXN",
                        "item_doi"       => "DOI",
                        "item_edition"   => "Edition",
                        "item_editor"    => "Editor",
                        "item_publisher" => "Publisher",
                        "item_pubplace"  => "PubPlace",
                        "item_pubdate"   => "PubDate",
                    );
                } else if (in_array($processingType, array("pdf_general", "ebook_general", "book_general"))) {
                    return array(
                        "item_title"       => "Title",
                        "item_author"      => "Author",
                        "item_isxn"        => "ISXN",
                        "item_edition"     => "Edition",
                        "item_editor"      => "Editor",
                        "item_publisher"   => "Publisher",
                        "item_pubplace"    => "PubPlace",
                        "item_pubdate"     => "PubDate",
                        "item_callnumber"  => "Callnumber",
                    );
                } else if (in_array($processingType, array("pdf_chapter", "ebook_chapter", "book_chapter"))) {
                    return array(
                        "collection_title" => "Title",
                        "item_title"       => "ArticleTitle",
                        "item_author"      => "Author",
                        "item_incpages"    => "Pages",
                        "item_isxn"        => "ISXN",
                        "item_edition"     => "Edition",
                        "item_editor"      => "Editor",
                        "item_publisher"   => "Publisher",
                        "item_pubplace"    => "PubPlace",
                        "item_pubdate"     => "PubDate",
                        "item_callnumber"  => "Callnumber",
                    );
                } else {
                    return array(
                        'item_doi'         => "DOI",
                        'journal_title'    => "Title",
                        'journal_volume'   => "Volume",
                        'journal_issue'    => "Issue",
                        'journal_month'    => "JournalMonth",
                        'journal_year'     => "JournalYear",
                        'collection_title' => "Title",
                        'item_uri'         => "URI",
                        "item_title"       => "ArticleTitle",
                        "item_author"      => "Author",
                        "item_incpages"    => "Pages",
                        "item_isxn"        => "ISXN",
                        "item_edition"     => "Edition",
                        "item_editor"      => "Editor",
                        "item_publisher"   => "Publisher",
                        "item_pubplace"    => "PubPlace",
                        "item_pubdate"     => "PubDate",
                        "item_callnumber"  => "Callnumber",
                    );
                }
            }
            Reportinator::log("Model_metadata:: Cannot find an engine to derive a metadata map.", null, $this->silent);

            return false;
        }


        private function _exists($processingType)
        {
            if (!$this->exists($processingType)) {
                return $this->_setFakeProcessingType();
            }

            return $processingType;
        }


        private function _setFakeProcessingType()
        {
            return 'undetermined';
        }


        private function getMetadataFields($processingType)
        {
            return $this->metadataList[$processingType];
        }


        private function getMetadataTitles($processingType = null)
        {

            $general = array(
                'item_title'       => "Title",
                'item_author'      => "Author(s)",
                'item_publisher'   => "Publisher",
                'item_pubplace'    => "Publication Place",
                'item_pubdate'     => "Publication Date",
                'item_incpages'    => "Inclusive Pages",
                'item_doi'         => "DOI",
                'item_isxn'        => "ISBN",
                'item_callnumber'  => "Call Number",
                'journal_title'    => "Journal Title",
                'journal_volume'   => "Volume",
                'journal_issue'    => "Issue",
                'journal_month'    => "Journal Month",
                'journal_year'     => "Journal Year",
                'item_edition'     => "Edition",
                'item_editor'      => "Editor",
                'collection_title' => "Collection/Container Title",
                'item_uri'         => "URI"
            );

            if ($processingType === null) {
                return $general;
            } else {

                $titles = array(
                    // type => database_fields
                    "pdf_general"           => array(),

                    "pdf_article"           => array(
                        'item_title' => "Article Title",
                        'item_isxn'  => "ISSN"
                    ),

                    "pdf_chapter"           => array(
                        'collection_title' => "Title",
                        'item_title'       => "Chapter/Section Title"
                    ),

                    "pdf_other"             => array(),

                    "book_general"          => array(),

                    "book_chapter"          => array(
                        'collection_title' => "Title",
                        'item_title'       => "Chapter/Section Title"
                    ),

                    "ebook_general"         => array(),

                    "ebook_chapter"         => array(
                        'collection_title' => "Title",
                        'item_title'       => "Chapter/Section Title"
                    ),

                    "web_general"           => array(),

                    "electronic_article"    => array(
                        'item_title' => "Article Title",
                        'item_isxn'  => "ISSN"
                    ),

                    "stream_general"        => array(),

                    "stream_video"          => array(
                        'item_author'    => "Director(s)",
                        'item_publisher' => "Distributor",
                        'item_editor'    => "Producer(s)",
                        'item_callnumber'    => "Call Number",
                        'item_pubdate'   => "Year"
                    ),

                    "stream_music"          => array(
                        'item_author'    => "Performer(s)",
                        'item_publisher' => "Distributor",
                        'item_editor'    => "Composer(s)",
                        'item_callnumber' => "Call Number",
                        'item_pubdate'   => "Year"
                    ),


                    "physical_general"      => array(
                        'item_title' => "Item/Object Name"
                    ),

                    "physical_unknown_type" => array(
                        'item_title' => "Object Name"
                    ),

                    "undetermined"          => array(
                        'item_title' => "Object Name/Label"
                    ),

                );

                return array_merge($general, $titles[$processingType]);
            }
        }
    }
