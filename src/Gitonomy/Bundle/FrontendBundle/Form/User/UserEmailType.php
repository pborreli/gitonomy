<?php

namespace Gitonomy\Bundle\FrontendBundle\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class UserEmailType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('email', 'email');
        if ('register' !== $options['action']) {
            $builder->add('isDefault', 'checkbox');
        }
    }

    public function getName()
    {
        return 'useremail';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Gitonomy\Bundle\CoreBundle\Entity\Email',
            'action'     => '',
        );
    }
}