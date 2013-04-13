<?php

namespace Mparaiso\Doctrine\ORM;

use Doctrine\ORM\EntityManager;
use ReflectionObject;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Load Fixtures from a yaml resource
 */
class FixtureLoader {

    private $entities;
    private $resource;
    private $rawData;

    function __construct($resource) {
        $this->resource = $resource;
        $this->entities = array();
    }

    function getEntities() {
        return $this->entities;
    }

    function ParseDatesAsDateTime($bool) {
        $this->asDateTime = $bool;
    }

    function parse() {
        Yaml::enablePhpParsing();
        # parse yaml datas
        $this->rawData = Yaml::parse($this->resource, true, true);
        $config_values = array_merge($this->rawData);
        # pour chaque fixture
        foreach ($config_values['fixtures'] as $entity_def) {
            # obtenir la classe
            $class = $entity_def['entity'];
            /* @note @php changer une propriété privée d'un champ */
            # instancier la classe
            //$prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($class), $class));
            $entity = new $class; //clone $prototype;
            $ref = new ReflectionObject($entity);
            # pour chaque champ du fixture
            foreach ($entity_def['fields'] as $field => $value) {
                # si la valeur est un tableau
                if (is_array($value)) {
                    # si la valeur est du time datetime
                    if (isset($value['datetime'])) {
                        $value = \DateTime::createFromFormat('U', $value['datetime']);
                    } else {
                        $tmp = new ArrayCollection;
                        # pour chaque valeur du tableau
                        foreach ($value as $name) {
                            # réferencer l'entité déja instanciée dans le tableau
                            $tmp[] = $this->entities[$name];
                        }
                        $value = $tmp;
                    }
                    # si valeur entourée de % % , réferencer l'entité correspondante
                } elseif (preg_match("#^\%(?P<name>.*)\%$#", $value, $matches) > 0) {
                    $value = $this->entities[$matches['name']];
                }
                $field = $ref->getProperty($field);
                $field->setAccessible(true);
                # affecter la valeur du champ à la proprièter
                $field->setValue($entity, $value);
            }

            $name = isset($entity_def['name']) ? $entity_def['name'] : uniqid();

            $this->entities[$name] = $entity;
        }
        # retourner les entitées
        return $this->entities;
    }

    function persistFixtures(EntityManager $em) {
        foreach ($this->entities as $entity) {
            $em->persist($entity);
        }
        return $em->flush();
    }

    function removeFixtures(EntityManager $em) {
        foreach ($this->entities as $entity) {
            $em->remove($entity);
        }
        return $em->flush();
    }

}



/*

## fixture example :
## YAML Template.
 
fixtures:
        
  - entity: Entity\Rdv\Rsvp
    name: simon
    fields:
      attendeeName : simon
      
  - entity: Entity\Rdv\Rsvp
    name: jacob
    fields:
      attendeeName: jacob
      

  - entity: Entity\Rdv\Dinner
    name: 'Italian dinner'
    fields:
      country: France
      latitude: 1
      longitude: 2
      contactPhone: 911
      address: Paris
      description: Italian Food
      title: Let's eat italian
      hostedBy: me
      eventDate:  { datetime: 2010-10-20 } # a DateTimeObject
      rsvps : [ jacob ] # an array collection , reference to the fixture named jacob
  
  - entity: Entity\Rdv\Dinner
    name: 'German dinner'
    fields:
      country: Germany
      latitude: 10
      longitude: 23
      contactPhone: 911-343-333
      address: Berlin
      description: Sausages
      title: Kraut party
      hostedBy: Von Brohm
      eventDate: { datetime: 2013-10-20 }
      rsvps: [ simon ]
      
  - entity: Entity\Rdv\Rsvp
    name: jean
    fields:
      attendeeName: jean
      dinner: %German dinner% # a reference to another fixture name , surrounded by quotes


    
    
    
    */