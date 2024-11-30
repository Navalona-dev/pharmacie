<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Privilege;
use App\Entity\Permission;
use Symfony\Component\Yaml\Yaml;
use App\Entity\Categoryofpermission;
use Doctrine\ORM\EntityManagerInterface;
use function Symfony\Component\String\u;
use Symfony\Component\Filesystem\Filesystem;
use App\Repository\CategoryofpermissionRepository;

class DefaultsLoader
{
    private $em;
    private $categoryRepo;

    public function __construct(
        EntityManagerInterface $em,
        CategoryofpermissionRepository $categoryRepo
        )
    {
        $this->em = $em;
        $this->categoryRepo = $categoryRepo;
    }

    private function maybeCreate($class, $criteria, ?string $repositoryMethodName = 'findOneBy'): array
    {
        $entity = $this->em->getRepository($class)->{$repositoryMethodName}($criteria);
        $isNew = is_null($entity);
        if ($isNew) {
            $entity = new $class;
        }
        return [$isNew, $entity];
    }

    public function loadDb()
    {
        $this->categoryPermisisons();
        $this->permissions();
        $this->privileges();
        $this->addPrivilegeInUser();
        $this->copyFiles();

    }

    public function categoryPermisisons() {
        $categoryPermisisons = Yaml::parseFile('defaults/data/categorie_permission.yaml');
    
        foreach ($categoryPermisisons as $label => $content) {
            list($isNew, $category) = $this->maybeCreate(Categoryofpermission::class, ['label' => $label]);
            if($isNew){
                $category->setLabel($label);
                if(isset($content['title'])) $category->setTitle($content['title']);
                if(isset($content['description'])) $category->setDescription($content['description']);
                $category->setCreatedAt(new \DateTime());

                $this->em->persist($category);
                $this->em->flush();
            }
    
        }
    }

    public function permissions() {
        $permissions = Yaml::parseFile('defaults/data/permission.yaml');
    
        foreach ($permissions as $label => $content) {
            list($isNew, $permission) = $this->maybeCreate(Permission::class, ['label' => $label]);
            if($isNew){
                $category = $this->categoryRepo->findOneBy(['id' => $content['categoryId'] ]);
                $permission->setLabel($label);
                if(isset($content['title'])) $permission->setTitle($content['title']);
                if(isset($content['description'])) $permission->setDescription($content['description']);
                $permission->setCreatedAt(new \DateTime());
                $permission->setCategoryofpermission($category);

                $this->em->persist($permission);
                $this->em->flush();
            }
    
        }
    }

    public function privileges() {
        $privileges = Yaml::parseFile('defaults/data/privilege.yaml');
    
        foreach ($privileges as $label => $content) {
            list($isNew, $privilege) = $this->maybeCreate(Privilege::class, ['label' => $label]);
            if($isNew){
                $privilege->setLabel($label);
                if(isset($content['title'])) $privilege->setTitle($content['title']);
                if(isset($content['description'])) $privilege->setDescription($content['description']);
                $privilege->setCreatedAt(new \DateTime());

                // Traiter les permisisonIds
            if (isset($content['permisisonIds'])) {
                $permissionIds = explode(',', $content['permisisonIds']); // Convertir la chaîne en tableau
                foreach ($permissionIds as $permissionId) {
                    $permission = $this->em->getRepository(Permission::class)->find(trim($permissionId));
                    if ($permission) {
                        $privilege->addPermission($permission); // Ajouter la permission au privilège (relation ManyToMany)
                    } else {
                        throw new \Exception("Permission avec l'ID {$permissionId} introuvable.");
                    }
                }
            }

                $this->em->persist($privilege);
                $this->em->flush();
            }
    
        }
    }

    public function addPrivilegeInUser() {
        $user =  $this->em->getRepository(User::class)->findOneBy(['id' => 1]);
        $privilege = $this->em->getRepository(Privilege::class)->findOneBy(['id' => 1]);
        $user->addPrivilege($privilege);
        $privilege->addUser($user);
        $this->em->flush();
    }

    public function copyFiles()
    {
        $fs = new Filesystem();
        $fileDefs = Yaml::parseFile('defaults/files.yaml') ?? [];
        foreach ($fileDefs as $destDir => $fileMappings) {
            foreach ($fileMappings as $dest => $source) {
                $destFile = u('/')->join([u($destDir), $dest]);
                $fs->copy($source, $destFile);
            };
        };
    }
    

}
