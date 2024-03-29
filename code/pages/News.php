<?php
/**
 * Created by PhpStorm.
 * User: hadleelineham
 * Date: 22/02/19
 * Time: 9:33 AM
 */

namespace Toast\News;

use Toast\News\Article;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\GroupedList;
use SilverStripe\TagField\TagField;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\ListboxField;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Taxonomy\TaxonomyTerm;
use SilverStripe\Taxonomy\TaxonomyType;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Lumberjack\Model\Lumberjack;
use Toast\QuickBlocks\GridFieldArchiveAction;
use Toast\QuickBlocks\GridFieldContentBlockState;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\ORM\PaginatedList;

class News extends \Page
{
    private static $singular_name = 'News';
    private static $plural_name = 'News';
    private static $description = 'Holder for Articles';
    private static $table_name = 'News';

    private static $extensions = [
        Lumberjack::class,
    ];

    private static $allowed_children = [
        Article::class,
    ];

    private static $has_many = [
        'Articles' => Article::class,
    ];

    private static $many_many = [
        'Filters' => TaxonomyType::class,
    ];

    

    /**
     * Return articles.
     *
     * @return DataList of Article objects
     */
    public function getCustomArticles()
    {
        $request = Controller::curr()->getRequest();
        if ($request->getVar('category') && $request->getVar('tags')){
            $categoryID = TaxonomyTerm::get()->filter(['Slug' => $request->getVar('category')])->first()->ID;

            $tagID = TaxonomyTerm::get()->filter(['Slug' => $request->getVar('tags')])->first()->ID;

            $articles = Article::get()->filter('ParentID', $this->ID)->filter(['Tags.ID' => $tagID,  'Categories.ID' => $categoryID]);

        }elseif ($request->getVar('category')){
            $categoryID = TaxonomyTerm::get()->filter(['Slug' => $request->getVar('category')])->first()->ID;
            if ($categoryID){
                $articles = Article::get()->filter('ParentID', $this->ID)->filter(['Categories.ID' => $categoryID]);
            }

        }elseif ($request->getVar('tags')){
            $tagSlugs = explode('+', $request->getVar('tags'));
            $tagIDs = TaxonomyTerm::get()->filter(['Slug' => $tagSlugs])->map('ID', 'ID')->toArray();

            $articles = Article::get()->filter('ParentID', $this->ID)->filter(['Tags.ID' => $tagIDs]);

        }else{
            $articles = Article::get()->filter('ParentID', $this->ID);
        }

        $Sort = 'PublishDate DESC';


        return $articles->sort($Sort);
    }

    public function children(){
        return  Article::get()->filter('ParentID', $this->ID);
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['Tags']);
        // $main = $fields->fieldByName('Root')->fieldByName('ChildPages');
        // $main->setTitle('Articles');
        // $fields->dataFieldByName('ChildPages')->setTitle('');

       $filterField = TagField::create('Filters', 'Filter Types', TaxonomyType::get(), $this->Filters());
       $fields->addFieldToTab('Root.Filters', $filterField);

        return $fields;
    }


    public function getCategories(){
        $articles = Article::get()->filter('ParentID', $this->ID);
        $terms = new ArrayList();
        foreach ($articles as $article){
            foreach ($article->Categories() as $category){
                if ($category->TypeID == 9){
                    $terms->push($category);
                }

            }
        }
        $terms->removeDuplicates('ID');

        return $terms;
    }

    public function getTags(){
        $articles = Article::get()->filter('ParentID', $this->ID);
        $terms = new ArrayList();
        foreach ($articles as $article){
            foreach ($article->Tags() as $tag){
                $terms->push($tag);
            }
        }
        $terms->removeDuplicates('ID');

        return $terms;
    }

    public function getGroupedTags()
    {

        return GroupedList::create($this->getTags());
    }

    //Allow creation of tags after news is made
    //Allow creation of articles after news is made
}

class NewsController extends \PageController
{



    private static $allowed_actions = [
        'ajaxArticles'
    ];

    public function getFiltersForPage()
    {
        $Types = new ArrayList();

        

        foreach($this->Filters() as $filter){
            
            
            
            $Types->push([
                'Title' => $filter->Title,
                'Tags' => TaxonomyTerm::get()->filter(['TypeID' => $filter->ID])
            ]);
        }

        return $Types;
    }

    public function getPaginatedArticles()
    {
        $Articles =  new PaginatedList($this->Articles(), Controller::curr()->getRequest());
        $Articles->setPageLength(6);
        return $Articles;
    }


    public function ajaxArticles(HTTPRequest $request)
    {

        $filterRequest = $request->getVar('filters');
        $articles = $this->Articles();
        if ($filterRequest){
            $filters = new ArrayList();
            $filterSlugs = explode( ',',$filterRequest);
            $tagsID = TaxonomyTerm::get()->filter('Slug', $filterSlugs)->column('ID');
            $articles->filter('Tags.ID', $tagsID);

        }
        $totalItems = $articles->count();
        /** -----------------------------------------
         * Limit and offset
         * ----------------------------------------*/
        if ($offset = $request->getVar('offset')) {
            $articles = $articles->limit(9, $offset);
        } else {
            $articles = $articles->limit(9);
        }

        if ($offset == 0) {
            $showMore = $totalItems > 9;
        } else {
            $showMore = $offset + 9 <= $totalItems;
        }

        $articles =  new PaginatedList($articles, Controller::curr()->getRequest());
        $articles->setPageLength(6);

       return  $this->customise($articles)->renderWith(['Layout/ajaxBlogItems']);
        $jsonData = [
            'content'     => '',
            'total_items' => $totalItems,
            'count'       => $articles->count(),
            'show_more'   => $showMore
        ];
        return $this->getStandardJsonResponse($jsonData);
    }
    public function getStandardJsonResponse($data, $method = 'json', $message = '', $code = 200, $status = 'success')
    {
        $elapsed = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];

        $response = [
            'request' => $this->owner->getRequest()->httpMethod(),
            'status'  => $status, // success, error
            'method'  => $method,
            'elapsed' => number_format($elapsed * 1000, 0) . 'ms',
            'message' => $message,
            'code'    => $code,
            'data'    => $data
        ];

        return json_encode($response, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
}
