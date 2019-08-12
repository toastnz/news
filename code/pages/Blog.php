<?php
/**
 * Created by PhpStorm.
 * User: hadleelineham
 * Date: 22/02/19
 * Time: 9:33 AM
 */

namespace Toast\News;

use Toast\News\News;

class Blog extends News
{
    private static $singular_name = 'Blog';
    private static $plural_name = 'Blog';
    private static $description = 'Blog';
    private static $table_name = 'Blog';


    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['Tags']);
        $main = $fields->fieldByName('Root')->fieldByName('ChildPages');
        $main->setTitle('Posts');
        $fields->dataFieldByName('ChildPages')->setTitle('');
//        $field = ListboxField::create('Tags', 'Tags', TaxonomyTerm::get()->map('ID', 'Name')->toArray(), $this->Tags());
//        $fields->addFieldToTab('Root.Tags', $field);

        return $fields;
    }
    
}

class BlogController extends NewsController
{

}
