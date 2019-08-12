<?php

namespace Toast\News;

use CustomTagField;
use SilverStripe\Dev\Debug;
use Toast\Pages\ProductPage;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\DateField;
use SilverStripe\Security\Member;
use SilverStripe\TagField\TagField;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Taxonomy\TaxonomyTerm;
use SilverStripe\Taxonomy\TaxonomyType;

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

        foreach($this->Parent()->Filters() as $filter){
            // $field = TagField::create('FilterTag_' . $filter->ID, $filter->Title, TaxonomyTerm::get()->filter('Type.ID', $filter->ID), $this->Tags()->filter('Type.ID', $filter->ID));
            $field = CustomTagField::create(
                'FilterTag_' . $filter->ID,
                $filter->Title,
                TaxonomyTerm::get()->filter(['TypeID' => $filter->ID]),
                $this->owner->Tags()->filter(['TypeID' => $filter->ID])
            )->setShouldLazyLoad(true);
            $fields->addFieldToTab("Root.Filters",
                LiteralField::create('test', '<a target="_blank" href="/admin/taxonomy/SilverStripe-Taxonomy-TaxonomyTerm/EditForm/field/SilverStripe-Taxonomy-TaxonomyTerm/item/new">Create it under the Type of ' . $filter->Title . ' </a>')
            );    
            $fields->addFieldToTab('Root.Filters', $field);
        }

        
        

        // $field = ListboxField::create('Tags', 'Tags', TaxonomyTerm::get()->filter('Type.Name', 'News Tags')->map('ID', 'Name')->toArray(), $this->Tags());
        // $fields->addFieldToTab('Root.Tags', $field);

        $fields->addFieldsToTab('Root.Main', [
            CheckboxField::create('Featured', 'This is a Featured Post'),
            DateField::create('PublishDate', 'Publish date')
        ], 'Summary');





        return $fields;
    }

    function onBeforeWrite()
    {
        parent::onBeforeWrite();

        foreach ($this->Parent->filters() as $filter){
            $TypeID = $filter->ID;

            if ($this->owner->isInDb() && isset($_POST['FilterTag_'. $filter->ID])) {
                foreach ($_POST['FilterTag_'. $filter->ID] as $tag) {
                    
                    $term = TaxonomyTerm::get()->filter(['TypeID' => $TypeID, 'Title' => $tag]);
                    if ($term->count() >= 1) {
                        $this->owner->Tags()->add($term->first());
                    } else {
                        $taxonomyTerm = new TaxonomyTerm();
                        $taxonomyTerm->Name = $tag;
                        $taxonomyTerm->TypeID = $TypeID;
                        $taxonomyTerm->write();
                        $this->owner->Tags()->add($taxonomyTerm);
                        //if no create and add to this product
                    }
                }
            
                
            
            }

            unset($_POST['FilterTag_'. $filter->Title]);
        }
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
