<?php

namespace DP\VoipServer\MumbleServerBundle\Controller\Mumble;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use DP\VoipServer\MumbleServerBundle\Entity\Mumble;
use DP\VoipServer\MumbleServerBundle\Form\Mumble\MumbleType;

/**
 * Machine controller.
 *
 * @Route("/machine")
 */
class MachineController extends Controller
{
    /**
     * Lists all Mumble entities.
     *
     * @Route("/", name="machine_mumble")
     * @Method("GET")
     * @Template("DPMumbleServerBundle:Machine:index.html.twig")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('DPMumbleServerBundle:Mumble')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new Mumble entity.
     *
     * @Route("/", name="machine_mumble_create")
     * @Method("POST")
     * @Template("DPMumbleServerBundle:Machine:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new Mumble();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('machine_mumble_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
    * Creates a form to create a Mumble entity.
    *
    * @param Mumble $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(Mumble $entity)
    {
        $form = $this->createForm(new MumbleType(), $entity, array(
            'action' => $this->generateUrl('machine_mumble_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Mumble entity.
     *
     * @Route("/new", name="machine_mumble_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new Mumble();
        $form   = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a Mumble entity.
     *
     * @Route("/{id}", name="machine_mumble_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DPMumbleServerBundle:Mumble')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Mumble entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Mumble entity.
     *
     * @Route("/{id}/edit", name="machine_mumble_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DPMumbleServerBundle:Mumble')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Mumble entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a Mumble entity.
    *
    * @param Mumble $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Mumble $entity)
    {
        $form = $this->createForm(new MumbleType(), $entity, array(
            'action' => $this->generateUrl('machine_mumble_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Mumble entity.
     *
     * @Route("/{id}", name="machine_mumble_update")
     * @Method("PUT")
     * @Template("DPMumbleServerBundle:Machine:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DPMumbleServerBundle:Mumble')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Mumble entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('machine_mumble_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a Mumble entity.
     *
     * @Route("/{id}", name="machine_mumble_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('DPMumbleServerBundle:Mumble')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Mumble entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('mumble'));
    }

    /**
     * Creates a form to delete a Mumble entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('machine_mumble_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}