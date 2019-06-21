<?php

namespace Toast\News;

use SilverStripe\Dev\Debug;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Member;
use SilverStripe\TagField\TagField;
use SilverStripe\Taxonomy\TaxonomyTerm;
use SilverStripe\Taxonomy\TaxonomyType;
use SilverStripe\View\ArrayData;
use Toast\Pages\ProductPage;

class Article extends \Page
{

    private static $singular_name = 'Article';
    private static $plural_name = 'Articles';
    private static $table_name = 'Article';

    private static $default_sort = '"PublishDate" IS NULL DESC, "PublishDate" DESC';

    /**
     * @var array
     */
    private static $db = [
        'PublishDate' => 'Date',
        'AuthorNames' => 'Varchar(1024)',
        'Featured' => 'Boolean(0)',
        'Summary'     => 'HTMLText'
    ];

    /**
     * @var array
     */
    private static $indexes = [
        'PublishDate' => true,
    ];

    /**
     * @var array
     */
    private static $many_many = [
        'Tags'       => TaxonomyTerm::class,
        'Categories' => TaxonomyTerm::class,
        'Authors'    => Member::class
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'ShowInMenus'     => false,
        'InheritSideBar'  => true,
        'ProvideComments' => true
    ];

    public function getClassNameForSearch(){
        return 'Article';
    }

    /**
     * @var array
     */
    private static $allowed_children = [];

    /**
     * @var bool
     */
    private static $can_be_root = false;

    /**
     * This will display or hide the current class from the SiteTree. This variable can be
     * configured using YAML.
     *
     * @var bool
     */
    private static $show_in_sitetree = false;

    //    list all tags that are on this news as tag and create fields
    // then I can add tags to each of the tag ie Month Year from the News


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['Terms']);
        $field = ListboxField::create('Categories', 'Categories', TaxonomyTerm::get()->filter('Type.Name', 'News Categories')->map('ID', 'Name')->toArray(), $this->Categories());
        $fields->addFieldToTab('Root.Categories', $field);

        $field = ListboxField::create('Tags', 'Tags', TaxonomyTerm::get()->filter('Type.Name', 'News Tags')->map('ID', 'Name')->toArray(), $this->Tags());
        $fields->addFieldToTab('Root.Tags', $field);

        $fields->addFieldsToTab('Root.Main', [
            CheckboxField::create('Featured', 'This is a Featured Post'),
            DateField::create('PublishDate', 'Publish date')
        ], 'Summary');





        return $fields;
    }

    function onBeforeWrite()
    {
        parent::onBeforeWrite();
    }

    public function getCustomSummary(){
        if ($this->Summary){
            return $this->Summary;
        }else{
            return $this->dbObject('Content')->LimitCharacters(130);
        }
    }

    public function getDisplayPublishedDate(){

        if ($this->PublishDate){
            $date = $this->dbObject("PublishDate")->format('d MMMM Y');
        }else{
            $date = $this->dbObject("LastEdited")->format('d MMMM Y');
        }

        return $date;
    }


//    public function getTags($type = 'Tags'){
//        return $this->Tags();
//
//    }


}

class ArticleController extends \PageController
{

}
