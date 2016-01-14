<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Form;

/**
 * Description of PagePresenter
 *
 * @author vsek
 */
class PagePresenter extends BasePresenterM{
    private $modules;
    
    /** @var \App\Model\Module\Page @inject */
    public $model;
    
    /**
     *
     * @var \Nette\Database\Table\ActiveRow
     */
    private $row = null;
    
    private $tree = array();
    
    public function startup() {
        parent::startup();
        $this->tree[0] = $this->translator->translate('page.noParent');
        
        $this->modules = array('' => $this->translator->translate('page.noModule'));
        if(isset($this->context->parameters['page']) && !is_null($this->context->parameters['page']['modules'])){
            foreach($this->context->parameters['page']['modules'] as $module){
                $this->modules[\Nette\Utils\Strings::webalize(str_replace('page.', '', $module))] = $this->translator->translate($module);
            }
        }
    }
    
    private function createTreeSelect(\Nette\Database\Table\ActiveRow $page = null, $level = 0){
        if(is_null($page)){
            $pages = $this->model->where('parent_id ?', null)->where('language_id', $this->webLanguage);
        }else{
            $pages = $this->model->where('parent_id = ?', $page['id'])->where('language_id', $this->webLanguage);
        }
        foreach($pages->order('position') as $pag){
            $name = '';
            for($i = 0; $i < $level; $i++){
                $name .= '-';
            }
            if(is_null($this->row) || $this->row['id'] != $pag['id']){
                $this->tree[$pag['id']] = $name .=  ' ' . $pag['name'];
            }
            $level++;
            $this->createTreeSelect($pag, $level);
            $level--;

        }
    }
    
    /**
     * Zmena poradi
     * @param integer $id ID polozky ktera se posouva
     * @param string $order smer posunuti
     */
    public function actionOrdering($id, $order){
        $this->exist($id);
        if($order == 'down'){
            $down = $this->model->where('position > ?', $this->row['position'])->where('language_id', $this->webLanguage)->where('parent_id ?', $this->row['parent_id'])->order('position ASC')->limit(1)->fetch();
            $position = $down->position;
            $down->update(array('position' => $this->row['position']));
            $this->row->update(array('position' => $position));
        }else{
            $up = $this->model->where('position < ?', $this->row['position'])->where('language_id', $this->webLanguage)->where('parent_id ?', $this->row['parent_id'])->order('position DESC')->limit(1)->fetch();
            $position = $up->position;
            $up->update(array('position' => $this->row->position));
            $this->row->update(array('position' => $position));
        }
        $this->flashMessage($this->translator->translate('page.orderChanged'));
        $this->redirect('default');
    }
    
    public function submitFormEdit(Form $form){
        $values = $form->getValues();
        if(!$values['external']){
            if($values['link'] == ''){
                $values['link'] = \Nette\Utils\Strings::webalize($values['name']);
            }else{
                $values['link'] = \Nette\Utils\Strings::webalize($values['link']);
            }
        }
        $data = array(
            'name' => $values->name,
            'link' => $values->link,
            'text' => $values->text,
            'title' => $values->title == '' ? null : $values->title,
            'keywords' => $values->keywords == '' ? null : $values->keywords,
            'description' => $values->description == '' ? null : $values->description,
            'module' => $values->module == '' ? null : $values->module,
            'is_homepage' => $values->is_homepage ? 'yes' : 'no',
            'in_menu' => $values->in_menu ? 'yes' : 'no',
            'parent_id' => (int)$values->parent_id == 0 ? null : (int)$values->parent_id,
            'h1' => $values->h1 == '' ? null : $values->h1,
            'external' => $values->external,
        );
        $this->row->update($data);
        
        $this->flashMessage($this->translator->translate('admin.form.editSuccess'));
        $this->redirect('edit', $this->row->id);
    }
    
    private function exist($id){
        $this->row = $this->model->where('language_id', $this->webLanguage)->where('id', $id)->fetch();
        if(!$this->row){
            $this->flashMessage($this->translator->translate('admin.text.notitemNotExist'), 'error');
            $this->redirect('default');
        }
    }
    
    protected function createComponentFormEdit($name){
        $form = new Form($this, $name);
        
        $this->createTreeSelect();
        
        $form->addText('name', $this->translator->translate('admin.form.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('link', $this->translator->translate('page.link'));
        $form->addCheckbox('is_homepage', $this->translator->translate('page.homepage'));
        $form->addCheckbox('in_menu', $this->translator->translate('page.showInMenu'))->setDefaultValue(true);
        $form->addCheckbox('external', $this->translator->translate('page.externalLink'));
        $form->addSelect('parent_id', $this->translator->translate('page.parentPage'), $this->tree);
        $form->addSelect('module', $this->translator->translate('page.module'), $this->modules);
        $form->addSpawEditor('text', $this->translator->translate('admin.form.text'));
        $form->addText('h1', $this->translator->translate('page.h1'));
        $form->addText('title', $this->translator->translate('page.title'));
        $form->addText('keywords', $this->translator->translate('page.keywords'));
        $form->addTextArea('description', $this->translator->translate('page.description'));
        
        $form->addSubmit('send', $this->translator->translate('admin.form.edit'));
        
        $form->onSuccess[] = $this->submitFormEdit;
        
        $form->setDefaults(array(
            'name' => $this->row->name,
            'link' => $this->row->link,
            'text' => $this->row->text,
            'title' => $this->row->title,
            'keywords' => $this->row->keywords,
            'description' => $this->row->description,
            'is_homepage' => $this->row->is_homepage == 'yes' ? true : false,
            'in_menu' => $this->row->in_menu == 'yes' ? true : false,
            'module' => $this->row->module,
            'parent_id' => $this->row->parent_id,
            'h1' => $this->row->h1,
            'external' => $this->row->external,
        ));
        
        return $form;
    }
    
    public function actionEdit($id){
        $this->exist($id);
    }
    
    public function actionDelete($id){
        $this->exist($id);
        $this->row->delete();
        $this->flashMessage($this->translator->translate('admin.text.itemDeleted'));
        $this->redirect('default');
    }
    
    public function submitFormNew(Form $form){
        $values = $form->getValues();

        if(!$values['external']){
            if($values->link == ''){
                $link = \Nette\Utils\Strings::webalize($values->name);
            }else{
                $link = \Nette\Utils\Strings::webalize($values->link);
            }
        }else{
            $link = $values['link'];
        }
        
        $this->model->insert(array(
            'name' => $values->name,
            'link' => $link,
            'text' => $values->text,
            'title' => $values->title == '' ? null : $values->title,
            'keywords' => $values->keywords == '' ? null : $values->keywords,
            'description' => $values->description == '' ? null : $values->description,
            'is_homepage' => $values->is_homepage ? 'yes' : 'no',
            'in_menu' => $values->in_menu ? 'yes' : 'no',
            'module' => $values->module == '' ? null : $values->module,
            'parent_id' => (int)$values->parent_id == 0 ? null : (int)$values->parent_id,
            'h1' => $values->h1 == '' ? null : $values->h1,
            'external' => $values->external,
            'language_id' => $this->webLanguage,
        ));
        
        $this->flashMessage($this->translator->translate('admin.text.inserted'));
        $this->redirect('default');
    }
    
    protected function createComponentFormNew($name){
        $form = new Form($this, $name);
        
        $this->createTreeSelect();
        
        $form->addText('name', $this->translator->translate('admin.form.name'))
                ->addRule(Form::FILLED, $this->translator->translate('admin.form.isRequired'));
        $form->addText('link', $this->translator->translate('page.link'));
        $form->addCheckbox('is_homepage', $this->translator->translate('page.homepage'));
        $form->addCheckbox('in_menu', $this->translator->translate('page.showInMenu'))->setDefaultValue(true);
        $form->addCheckbox('external', $this->translator->translate('page.externalLink'));
        $form->addSelect('parent_id', $this->translator->translate('page.parentPage'), $this->tree);
        $form->addSelect('module', $this->translator->translate('page.module'), $this->modules);
        $form->addSpawEditor('text', $this->translator->translate('admin.form.text'));
        $form->addText('h1', $this->translator->translate('page.h1'));
        $form->addText('title', $this->translator->translate('page.title'));
        $form->addText('keywords', $this->translator->translate('page.keywords'));
        $form->addTextArea('description', $this->translator->translate('page.description'));
        
        $form->addSubmit('send', $this->translator->translate('admin.form.create'));
        
        $form->onSuccess[] = $this->submitFormNew;
        
        return $form;
    }
    
    protected function createComponentGrid(){
        $grid = new \App\Grid\GridTree('page');

        $grid->setModel($this->model->where('parent_id ?', null)->where('language_id', $this->webLanguage));
        $grid->addColumn(new \App\Grid\Column\Column('name', $this->translator->translate('page.name')));
        $grid->addColumn(new \App\Grid\Column\Column('link', $this->translator->translate('page.link')));
        $grid->addColumn(new \App\Grid\Column\YesNo('in_menu', $this->translator->translate('page.inMenu')));
        $grid->addColumn(new \App\Grid\Column\Column('id', $this->translator->translate('admin.grid.id')));
        
        $grid->addMenu(new \App\Grid\Menu\Update('edit', $this->translator->translate('admin.form.edit')));
        $grid->addMenu(new \App\Grid\Menu\Delete('delete', $this->translator->translate('admin.grid.delete')));
        
        $grid->setOrder('position');
        
        $grid->setOrdering('ordering');
        
        return $grid;
    }
}
