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

namespace Gitonomy\Bundle\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Gitonomy\Bundle\CoreBundle\Entity\Project;

/**
 * @author Julien DIDIER <julien@jdidier.net>
 */
class LoadProjectData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        $foobar = new Project('Foobar', 'foobar');
        $foobar->setRepositorySize(256);
        $manager->persist($foobar);
        $this->setReference('project-foobar', $foobar);

        $empty = new Project('Empty', 'empty');
        $empty->setRepositorySize(256);
        $manager->persist($empty);
        $this->setReference('project-empty', $empty);

        $barbaz = new Project('Barbaz', 'barbaz');
        $barbaz->setRepositorySize(352);
        $manager->persist($barbaz);
        $this->setReference('project-barbaz', $barbaz);

        $manager->flush();
    }

    /**
     * @inheritdoc
     */
    public function getOrder()
    {
        return 1;
    }
}
