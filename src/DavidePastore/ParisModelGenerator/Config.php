<?php
namespace DavidePastore\ParisModelGenerator;

/**
 * Config class.
 *
 * @license MIT
 * @author Davide Pastore <pasdavide@gmail.com>
 */
class Config {
	
	/**
	 * 
	 * @var string
	 */
	private $namespace;
	
	/**
	 * 
	 * @var string
	 */
	private $destinationFolder;
	
	/**
	 * 
	 * @var array of tags
	 */
	private $tags = array();
	
	/**
	 * Get the namespace.
	 * @return string Returns the namespace.
	 */
	public function getNamespace(){
		return $this->namespace;
	}
	
	/**
	 * Set the namespace.
	 * @param string $namespace The new namespace.
	 */
	public function setNamespace($namespace){
		$this->namespace = $namespace;
	}
	
	/**
	 * Get the destination folder.
	 * @return string Returns the destination folder.
	 */
	public function getDestinationFolder(){
		return $this->destinationFolder;
	}
	
	/**
	 * Set the destination folder.
	 * @param string $destinationFolder The new destination folder.
	 */
	public function setDestinationFolder($destinationFolder){
		$this->destinationFolder = $destinationFolder;
	}
	
	/**
	 * Get the list of the tags.
	 * @return array An array of tags.
	 */
	public function getTags(){
		return $this->tags;
	}
	
	/**
	 * Set the tag value.
	 * @param array $tags The new tags.
	 */
	public function setTags($tags){
		$this->tags = $tags;
	}
	
	/**
	 * Add a new tag.
	 * @param array $tag The tag to add to the tags array.
	 */
	public function addTag($tag){
		array_push($this->tags, $tag);
	}
	
	/**
	 * Read the whole content of composer.json.
	 * @param string $file The path of composer.json file.
	 * @return array Returns the associative array with all the properties of composer.json.
	 */
	private function readComposerDotJson($file){
		return json_decode(file_get_contents($file), true);
	}
	
	/**
	 * Set parameters from the given composer.json file path.
	 * @param string $file The composer.json file path.
	 */
	public function createFromComposer($file = 'composer.json'){
		$composer = $this->readComposerDotJson($file);
		
		$this->setNamespace('\\Paris\\Model\\Generator');
		$this->setDestinationFolder('generated\\');
		
		if(isset($composer['extra']) && isset($composer['extra']['paris-model-generator'])){
			$configuration = $composer['extra']['paris-model-generator'];
				
			//Namespace
			if(isset($configuration['namespace'])){
				$this->setNamespace($configuration['namespace']);
			}
				
			//Destination Folder
			if(isset($configuration['destination-folder'])){
				$this->setDestinationFolder($configuration['destination-folder']);
			}
		}
		
		//Licenses
		if(isset($composer['license'])){
			$licenses = $composer['license'];
				
			if(!is_array($licenses)){
				$licenses = array($licenses);
			}
				
			//Convert to another format
			foreach ($licenses as $license){
				$tag = array(
						'name'        => 'license',
						'description' => $license,
				);
				$this->addTag($tag);
			}
		}
		
		//Authors
		if(isset($composer['authors'])){
			$authors = $composer['authors'];
				
			//Authors
			foreach ($authors as $author){
				$tag = array(
						'name'        => 'author',
						'description' => $author['name'] . ' <' . $author['email'] . '>',
				);
				$this->addTag($tag);
			}
		}
	}
}