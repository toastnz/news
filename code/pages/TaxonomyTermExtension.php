<?php

namespace Toast\News;


use Toast\Model\Guest;
use Toast\News\Article;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\FieldList;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;

class TaxonomyTermExtension extends DataExtension
{
    private static $db = [
        'Slug' => 'Text',
        'Title' => 'Varchar'
    ];
    private static $belongs_many_many = [
        'Articles' => Article::class,
        'Guests' => Guest::class,
    ];

    public function getCategoryLink($holderLink = null){
        $request = Controller::curr()->getRequest();
        if (isset($holderLink)){
            $url = $holderLink;
        }else{
            if (Controller::curr()->ClassNameForTemplate == 'Article'){
                $url = Controller::curr()->Parent()->AbsoluteLink();
            }else{
                $url = $request->getURL();
            }
        }



        // strip out all whitespace
        if ($this->owner->Slug){
            $slug = $this->owner->Slug;
        }else{
            $slug = preg_replace('/\s*/', '', $this->owner->Name);
            // convert the string to all lowercase
            $slug = strtolower($slug);
        }

        $query = $_GET;
        // replace parameter(s)
        if (!$this->isActiveCategory()){
            $query['category'] =  $slug;
        }else{
            $query['category'] = Null;
        }
        // rebuild url
        $query_result = http_build_query($query);
        // new link
        $url = $url . '?' . $query_result;
        return str_replace("??","?",$url);
    }

    public function getTagLink(){
        $request = Controller::curr()->getRequest();
        if (Controller::curr()->ClassNameForTemplate == 'Article'){
            $url = Controller::curr()->Parent()->URLSegment;
        }else{
            $url = $request->getURL();
        }
        // strip out all whitespace
        if ($this->owner->Slug){
            $slug = $this->owner->Slug;
        }else{
            $slug = preg_replace('/\s*/', '', $this->owner->Name);
            // convert the string to all lowercase
            $slug = strtolower($slug);
        }
        $request = Controller::curr()->getRequest();
        $query = $_GET;
        // replace parameter(s)
        if (!$this->isActiveTag()){
            if (isset($query['tags'])){
                $query['tags'] =  $slug;
            }else{
                $query['tags'] = $slug;
            }
        }else{
            $query['tags'] = str_replace($slug,"",$query['tags']);
            $query['tags'] = str_replace("++","+",$query['tags']);
            $query['tags'] = rtrim($query['tags'], '+');
            $query['tags'] = ltrim($query['tags'], '+');
            if ($query['tags']){

            }else{
                $query['tags'] = Null;
            }

        }
        // rebuild url

        $query_result = http_build_query($query);
//        Debug::show($query_result);
//        die();
        // new link

        $url = $url . '?' . $query_result;
        // return str_replace("??","?",$url);
        return Controller::join_links(Director::absoluteBaseURL(), str_replace("??","?",$url));
    }


    public function getAjaxTagLink(){
        $request = Controller::curr()->getRequest();
        if (Controller::curr()->ClassNameForTemplate == 'Article'){
            $url = Controller::curr()->Parent()->AbsoluteLink();
        }else{
            $url = $request->getURL();
        }
        // strip out all whitespace
        if ($this->owner->Slug){
            $slug = $this->owner->Slug;
        }else{
            $slug = preg_replace('/\s*/', '', $this->owner->Name);
            // convert the string to all lowercase
            $slug = strtolower($slug);
        }
        $request = Controller::curr()->getRequest();
        $query = $_GET;
        // replace parameter(s)
        if (!$this->isActiveTag()){
            if (isset($query['tags'])){
                $query['tags'] =  $slug;
            }else{
                $query['tags'] = $slug;
            }
        }else{
            $query['tags'] = str_replace($slug,"",$query['tags']);
            $query['tags'] = str_replace("++","+",$query['tags']);
            $query['tags'] = rtrim($query['tags'], '+');
            $query['tags'] = ltrim($query['tags'], '+');
            if ($query['tags']){

            }else{
                $query['tags'] = Null;
            }

        }
        // rebuild url

        $query_result = http_build_query($query);
//        Debug::show($query_result);
//        die();
        // new link

        $url = $url . '?' . $query_result;

        $url = str_replace('ajaxFAQS', '', $url);
        $url = rtrim($url, '?');

        return Controller::join_links(Director::absoluteBaseURL(), str_replace("??","?",$url));
    }

    public function updateCMSFields(FieldList $fields)
    {
        parent::updateCMSFields($fields); // TODO: Change the autogenerated stub
        $fields->removeByName(['Slug']);
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite(); // TODO: Change the autogenerated stub
        $slug = preg_replace('/\s*/', '', $this->owner->Name);
        // convert the string to all lowercase
        $slug = strtolower($slug);

        if ($this->owner->parent()->Children()->filter(['Slug' => $slug])->count() > 1){
            $numberAddon = $this->owner->parent()->Children()->filter(['Slug' => $slug])->count() + 1;
            $this->owner->Slug = $slug . '-' . $numberAddon;
        }else{
            $this->owner->Slug = $slug;
        }



    }

    public function isActiveTag(){
        
        if (isset($_GET["tags"])){

        
            $tags = explode('+', $_GET["tags"]);
            foreach ($tags as $tag){
                // Debug::dump($this->owner->Slug);
                $matchFound = ( $tag == $this->owner->Slug );
                // $matchFound = ( $tag && trim($tag) == $this->owner->Slug );
                if ($matchFound){
                
                    return true;
                }
            }
            $matchFound = ( isset($_GET["tags"]) && trim($_GET["tags"]) == $this->owner->Slug );
        
            if ($matchFound){
                return true;
            }

            
        }


    }

    public function isActiveCategory(){
        $matchFound = ( isset($_GET["category"]) && trim($_GET["category"]) == $this->owner->Slug );
        if ($matchFound){
            return true;
        }

    }


}