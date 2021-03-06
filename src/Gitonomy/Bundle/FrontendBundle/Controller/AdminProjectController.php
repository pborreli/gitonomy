<?php

/**
 * This file is part of Gitonomy.
 *
 * (c) Alexandre Salomé <alexandre.salome@gmail.com>
 * (c) Julien DIDIER <genzo.wm@gmail.com>
 *
 * This source file is subject to the GPL license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Gitonomy\Bundle\FrontendBundle\Controller;

use Gitonomy\Bundle\CoreBundle\Entity\Role;
use Gitonomy\Bundle\CoreBundle\EventDispatcher\GitonomyEvents;
use Gitonomy\Bundle\CoreBundle\EventDispatcher\Event\ProjectEvent;

use Gitonomy\Bundle\CoreBundle\Entity\UserRoleProject;
use Gitonomy\Bundle\CoreBundle\Entity\ProjectGitAccess;

/**
 * @author Julien DIDIER <julien@jdidier.net>
 * @author Alexandre Salomé <alexandre.salome@gmail.com>
 */
class AdminProjectController extends BaseAdminController
{
    public function getMessage($object, $type)
    {
        if ($type == self::MESSAGE_TYPE_CREATE) {
            return sprintf('Project "%s" is created', $object->getName());
        } elseif ($type == self::MESSAGE_TYPE_UPDATE) {
            return sprintf('Project "%s" is updated', $object->getName());
        } elseif ($type == self::MESSAGE_TYPE_DELETE) {
            return sprintf('Project "%s" is deleted', $object->getName());
        }

        throw new \InvalidArgumentException('Unknown type '.$type);
    }

    /**
     * Displays list of Git accesses to the project.
     */
    public function gitAccessesAction($id)
    {
        $this->assertGranted('ROLE_GIT_ACCESS_LIST');
        $project = $this->getProject($id);

        $gitAccess = new ProjectGitAccess($project);
        $form      = $this->createForm('project_git_access', $gitAccess);

        $request = $this->getRequest();
        if ('POST' === $request->getMethod()) {
            $this->assertGranted('ROLE_GIT_ACCESS_CREATE');
            $form->bindRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($gitAccess);
                $em->flush();

                $this->get('session')->setFlash('success', 'New git access saved!');

                return $this->redirect($this->generateUrl('gitonomyfrontend_adminproject_gitAccesses', array('id' => $project->getId())));
            }
        }

        return $this->render('GitonomyFrontendBundle:AdminProject:gitAccesses.html.twig', array(
            'object' => $project,
            'form'   => $form->createView()
        ));
    }

    public function gitAccessDeleteAction($gitAccessId)
    {
        $this->assertGranted('ROLE_GIT_ACCESS_DELETE');

        $em         = $this->getDoctrine()->getEntityManager();
        $repository = $em->getRepository('GitonomyCoreBundle:ProjectGitAccess');

        if (!$gitAccess = $repository->find($gitAccessId)) {
            throw $this->createNotFoundException(sprintf('No Git access found with id "%d".', $gitAccessId));
        }

        $form    = $this->createFormBuilder()->getForm();
        $request = $this->getRequest();

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $em->remove($gitAccess);
                $em->flush();

                $this->get('session')->setFlash('success', 'Access deleted');

                return $this->redirect($this->generateUrl($this->getRouteName('gitAccesses'), array(
                    'id' => $gitAccess->getProject()->getId()
                )));
            }
        }

        return $this->render('GitonomyFrontendBundle:AdminProject:gitAccessDelete.html.twig', array(
            'object'       => $gitAccess->getProject(),
            'gitAccess'    => $gitAccess,
            'form'         => $form->createView(),
        ));
    }

    /**
     * Action to create a new user role project for an user.
     */
    public function userRolesAction($id)
    {
        $this->assertGranted('ROLE_USER_PROJECT_LIST');
        $project = $this->getProject($id);

        $userRoleProject = new UserRoleProject();
        $em              = $this->getDoctrine()->getEntityManager();
        $repository      = $em->getRepository('GitonomyCoreBundle:User');
        $usedUsers       = $repository->findByProject($project);
        $totalUsers      = $repository->findAll();
        $request         = $this->getRequest();

        $form = $this->createForm('adminuserroleproject', $userRoleProject, array(
            'usedUsers' => $usedUsers,
            'from'      => 'project',
        ));

        if ('POST' == $request->getMethod()) {
            $this->assertGranted('ROLE_USER_PROJECT_CREATE');
            $form->bindRequest($request);
            if ($form->isValid()) {
                $userRoleProject->setProject($project);
                $em->persist($userRoleProject);
                $em->flush();

                $this->get('session')->setFlash('success', sprintf(
                    'Removed user "%s" from project "%s"',
                    $userRoleProject->getUser()->getFullname(),
                    $userRoleProject->getProject()->getName()
                ));

                return $this->redirect($this->generateUrl($this->getRouteName('userRoles'), array('id' => $project->getId())));
            }
        }

        return $this->render('GitonomyFrontendBundle:AdminProject:userRoles.html.twig', array(
            'object'      => $project,
            'form'        => $form->createView(),
            'displayForm' => $totalUsers > $usedUsers,
        ));
    }

    public function userRoleDeleteAction($userRoleId)
    {
        $this->assertGranted('ROLE_USER_PROJECT_DELETE');

        $em         = $this->getDoctrine()->getEntityManager();
        $repository = $em->getRepository('GitonomyCoreBundle:UserRoleProject');

        if (!$userRole = $repository->find($userRoleId)) {
            throw $this->createNotFoundException(sprintf('No UserRole found with id "%d".', $userRoleId));
        }

        $form    = $this->createFormBuilder()->getForm();
        $request = $this->getRequest();

        if ('POST' == $request->getMethod()) {
            $form->bindRequest($request);
            if ($form->isValid()) {
                $em->remove($userRole);
                $em->flush();

                $this->get('session')->setFlash('success', sprintf(
                    'User "%s" removed from project "%s".',
                    $userRole->getUser()->getFullname(),
                    $userRole->getProject()->getName()
                ));

                return $this->redirect($this->generateUrl($this->getRouteName('userRoles'), array(
                    'id' => $userRole->getProject()->getId()
                )));
            }
        }

        return $this->render('GitonomyFrontendBundle:AdminProject:userRoleDelete.html.twig', array(
            'object'       => $userRole->getProject(),
            'userRole'     => $userRole,
            'form'         => $form->createView(),
        ));
    }

    /**
     * @see BaseAdminController:postCreate
     */
    protected function postCreate($object)
    {
        $this->get('event_dispatcher')->dispatch(GitonomyEvents::PROJECT_CREATE, new ProjectEvent($object));
        $em = $this->get('doctrine')->getManager();

        $em->persist($object);
        $em->flush();
    }

    /**
     * @see BaseAdminController:preDelete
     */
    protected function preDelete($object)
    {
        $this->get('event_dispatcher')->dispatch(GitonomyEvents::PROJECT_DELETE, new ProjectEvent($object));
    }

    /**
     * @see BaseAdminController::listAction
     */
    public function listAction()
    {
        $this->assertGranted('ROLE_PROJECT_LIST');

        return parent::listAction();
    }

    /**
     * @see BaseAdminController::createAction
     */
    public function createAction()
    {
        $this->assertGranted('ROLE_PROJECT_CREATE');

        return parent::createAction();
    }

    /**
     * @see BaseAdminController::editAction
     */
    public function editAction($id)
    {
        $this->assertGranted('ROLE_PROJECT_EDIT');

        return parent::editAction($id);
    }

    /**
     * @see BaseAdminController::deleteAction
     */
    public function deleteAction($id)
    {
        $this->assertGranted('ROLE_PROJECT_DELETE');

        return parent::deleteAction($id);
    }

    /**
     * Returns the project or throws an exception if the project was not found.
     *
     * @return Gitonomy\Bundle\CoreBundle\Entity\Project
     */
    protected function getProject($id)
    {
        $project = $this->getRepository()->find($id);
        if (null === $project) {
            throw $this->createNotFoundException(sprintf("Project #%s not found", $id));
        }

        return $project;
    }

    /**
     * @see BaseAdminController:getRepository
     * @return Gitonomy\Bundle\CoreBundle\Repository\ProjectRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getEntityManager()->getRepository('GitonomyCoreBundle:Project');
    }
}
