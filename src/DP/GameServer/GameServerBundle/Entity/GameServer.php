<?php

/*
** Copyright (C) 2010-2013 Kerouanton Albin, Smedts Jérôme
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along
** with this program; if not, write to the Free Software Foundation, Inc.,
** 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

namespace DP\GameServer\GameServerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DP\Core\MachineBundle\Entity\Machine;
use Symfony\Component\Validator\Constraints as Assert;
use DP\GameServer\GameServerBundle\Query\QueryInterface;
use DP\GameServer\GameServerBundle\Query\RconInterface;
use DP\Core\MachineBundle\PHPSeclibWrapper\PHPSeclibWrapper;
use DP\GameServer\GameServerBundle\Exception\InvalidPathException;
use DP\GameServer\GameServerBundle\Exception\NotImplementedException;
use DP\GameServer\GameServerBundle\FTP\AbstractItem;
use DP\GameServer\GameServerBundle\FTP\File;
use DP\GameServer\GameServerBundle\FTP\Directory;
use DP\Core\GameBundle\Entity\Plugin;

/**
 * DP\Core\GameServer\GameServerBundle\Entity\GameServer
 * @ORM\Table(name="gameserver")
 * @ORM\Entity()
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({
 *      "steam" = "DP\GameServer\SteamServerBundle\Entity\SteamServer",
 *      "minecraft" = "DP\GameServer\MinecraftServerBundle\Entity\MinecraftServer"
 * })
 */
abstract class GameServer
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=32)
     * @Assert\NotBlank(message="gameServer.assert.name")
     */
    protected $name;

    /**
     * @var integer $port
     *
     * @ORM\Column(name="port", type="integer")
     * @Assert\Range(
     *      min = 1024, minMessage = "gameServer.assert.port",
     *      max = 65536, maxMessage = "gameServer.assert.port"
     * )
     * @Assert\NotBlank(message="gameServer.assert.port")
     */
    protected $port;

    /**
     * @var integer $installationStatus
     *
     * @ORM\Column(name="installationStatus", type="integer", nullable=true)
     */
    protected $installationStatus;

    /**
     * @var string $dir
     *
     * @ORM\Column(name="dir", type="string", length=64)
     * @Assert\NotBlank(message="gameServer.assert.dir")
     */
    protected $dir;

    /**
     * @var integer $maxplayers
     *
     * @ORM\Column(name="maxplayers", type="integer")
     * @Assert\Range(min = 2, minMessage = "gameServer.assert.maxplayers")
     */
    protected $maxplayers;

    /**
     * @ORM\ManyToOne(targetEntity="DP\Core\MachineBundle\Entity\Machine", inversedBy="gameServers")
     * @ORM\JoinColumn(name="machineId", referencedColumnName="id")
     * @Assert\NotNull(message="gameServer.assert.machine")
     */
    protected $machine;

    /**
     * @ORM\ManyToOne(targetEntity="DP\Core\GameBundle\Entity\Game", inversedBy="gameServers")
     * @ORM\JoinColumn(name="gameId", referencedColumnName="id")
     * @Assert\NotNull(message="gameServer.assert.game")
     */
    protected $game;

    /**
     * @var string $rcon
     *
     * @ORM\Column(name="rconPassword", type="string", length=32)
     * @Assert\NotNull(message="gameServer.assert.rconPassword")
     */
    protected $rconPassword;

    protected $query;
    protected $rcon;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection $plugins
     *
     * @ORM\ManyToMany(targetEntity="DP\Core\GameBundle\Entity\Plugin")
     * @ORM\JoinTable(name="gameserver_plugins",
     *      joinColumns={@ORM\JoinColumn(name="server_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="plugin_id", referencedColumnName="id")}
     * )
     */
    private $plugins;
    
    /** @var boolean $alreadyInstalled Used by the add form, and the create process **/
    private $alreadyInstalled;


    abstract public function changeStateServer($state);


    public function __construct()
    {
        $this->plugins = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set machine
     *
     * @param Machine $machine
     */
    public function setMachine(Machine $machine)
    {
        $this->machine = $machine;
    }

    /**
     * Get machine
     *
     * @return Machine
     */
    public function getMachine()
    {
        return $this->machine;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set port
     *
     * @param integer $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * Get port
     *
     * @return integer
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set game
     *
     * @param Game $game
     */
    public function setGame($game)
    {
        $this->game = $game;
    }

    /**
     * Get gameId
     *
     * @return integer
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * Set installationStatus
     *
     * @param integer $installationStatus
     */
    public function setInstallationStatus($installationStatus)
    {
        $this->installationStatus = $installationStatus;
    }

    /**
     * Get installationStatus
     *
     * @return integer
     */
    public function getInstallationStatus()
    {
        return $this->installationStatus;
    }

    /**
     * Set dir
     *
     * @param string $dir
     */
    public function setDir($dir)
    {
        $this->dir = trim($dir, '/ ');
    }

    /**
     * Get dir
     *
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * Set maxplayers
     *
     * @param integer $maxplayers
     */
    public function setMaxplayers($maxplayers)
    {
        $this->maxplayers = $maxplayers;
    }

    /**
     * Get maxplayers
     *
     * @return integer
     */
    public function getMaxplayers()
    {
        return $this->maxplayers;
    }

    /**
     * Get absolute path of server installation directory
     *
     * @return string
     */
    public function getAbsoluteDir()
    {
        return $this->getMachine()->getHome() . '/' . $this->getDir() . '/';
    }

    /**
     * Get absolute path of binaries directory
     *
     * @return string
     */
    public function getAbsoluteBinDir()
    {
        return $this->getAbsoluteDir() . $this->getGame()->getBinDir() . '/';
    }

    /**
     * Get absolute path of game content directory
     *
     * @return string
     */
    public function getAbsoluteGameContentDir()
    {
        return $this->getAbsoluteBinDir();
    }

    public function getScreenName()
    {
        $screenName = $this->getMachine()->getUser() . '-' . $this->getDir();

        return $this->getScreenNameHash($screenName);
    }

    public function getInstallScreenName()
    {
        $screenName = $this->getMachine()->getUser() . '-install-' . $this->getDir();

        return $this->getScreenNameHash($screenName);
    }

    public function getPluginInstallScreenName($scriptName = '')
    {
        $screenName = $this->getMachine()->getUser() . '-plugin-install-' . $scriptName . '-' . $this->getDir();

        return $this->getScreenNameHash($screenName);
    }

    public function getScreenNameHash($screenName, $hashLength = 20)
    {
        $screenName = sha1($screenName);
        $screenName = substr($screenName, 0, $hashLength);

        return 'dp-' . $screenName;
    }

    public function setQuery(QueryInterface $query)
    {
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set rconPassword
     *
     * @param string $rconPassword
     */
    public function setRconPassword($rconPassword)
    {
        $this->rconPassword = $rconPassword;
    }

    /**
     * Get rconPassword
     *
     * @return string
     */
    public function getRconPassword()
    {
        return $this->rconPassword;
    }

    public function isEmptyRconPassword()
    {
        return empty($this->rconPassword);
    }

    public function setRcon(RconInterface $rcon)
    {
        $this->rcon = $rcon;

        return $this->rcon;
    }

    public function getRcon()
    {
        return $this->rcon;
    }
    
    public function getServerName()
    {
        return '[DediPanel] ' . $this->getName();
    }
    
    /**
     * Set whether is already already installed
     * 
     * @param boolean $alreadyInstalled
     * 
     * @return GameServer
     */
    public function setAlreadyInstalled($alreadyInstalled)
    {
        $this->alreadyInstalled = $alreadyInstalled;
        
        return $this;
    }
    
    /**
     * Is already installed ? (from form)
     * 
     * @return boolean
     */
    public function isAlreadyInstalled()
    {
        return $this->alreadyInstalled;
    }
    
    public function getContent(AbstractItem $item)
    {
        $fullpath = $item->getFullpath();
        
        if ($item instanceof Directory) {
            return $this->getDirContent($fullpath);
        }
        elseif ($item instanceof File) {
            return $this->getFileContent($fullpath);
        }
        else {
            throw new \RuntimeException('Not supported ftp resource type.');
        }
    }

    public function getDirContent($path = '')
    {
        $path = $this->getAbsoluteGameContentDir() . $path;
        $sftp = PHPSeclibWrapper::getFromMachineEntity($this->getMachine())->getSFTP();

        $dirContent = $sftp->rawlist($path);
        $dirs = array();
        $files = array();
        
        if ($dirContent == false) {
            throw new InvalidPathException();
        }

        foreach ($dirContent AS $key => $attr) {
            $attr['name'] = $key;

            if ($attr['type'] == NET_SFTP_TYPE_DIRECTORY
                && $key != '..' && $key != '.') {
                $dirs[] = $attr;
            }
            elseif ($attr['type'] == NET_SFTP_TYPE_REGULAR) {
                $files[] = $attr;
            }
        }

        return array('files' => $files, 'dirs' => $dirs);
    }

    public function getFileContent($path)
    {
        $path = $this->getAbsoluteGameContentDir() . $path;

        return utf8_encode(PHPSeclibWrapper::getFromMachineEntity($this->getMachine())
                ->getRemoteFile($path));
    }

    public function uploadFile($path, $content)
    {
        $path = $this->getAbsoluteGameContentDir() . $path;

        return PHPSeclibWrapper::getFromMachineEntity($this->getMachine())
                ->upload($path, $content, false);
    }

    public function touch($file)
    {
        $path = $this->getAbsoluteGameContentDir() . $file;

        return PHPSeclibWrapper::getFromMachineEntity($this->getMachine())
                ->touch($path);
    }

    /**
     * Add plugin
     *
     * @param \DP\Core\GameBundle\Entity\Plugin $plugin
     */
    public function addPlugin(\DP\Core\GameBundle\Entity\Plugin $plugin)
    {
        $this->plugins[] = $plugin;
    }

    /**
     * Remove a server plugin
     * @param \DP\Core\GameBundle\Entity\Plugin $plugin
     */
    public function removePlugin(\DP\Core\GameBundle\Entity\Plugin $plugin)
    {
        $this->plugins->removeElement($plugin);
    }

    /**
     * Get plugins recorded as "installed on the server"
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getPlugins()
    {
        if ($this->plugins instanceof \Doctrine\ORM\PersistentCollection) {
            return $this->plugins->getValues();
        }
        else {
            return $this->plugins;
        }
    }

    public function getInstalledPlugins()
    {
        return $this->getPlugins();
    }

    public function getNotInstalledPlugins()
    {
        $intersectCallback = function ($plugin1, $plugin2) {
            return $plugin1->getId() - $plugin2->getId();
        };
        $plugins = $this->getGame()->getPlugins()->getValues();

        // On compare l'array contenant l'ensemble des plugins dispo pour le jeu
        // A ceux installés sur le serveur
        return array_udiff($plugins, $this->getPlugins(), $intersectCallback);
    }

    public function fileExists($filepath)
    {
        $filepath = $this->getAbsoluteGameContentDir() . $filepath;

        return $this->getMachine()->fileExists($filepath);
    }

    public function dirExists($dirpath)
    {
        $dirpath = $this->getAbsoluteGameContentDir() . $dirpath;

        return $this->getMachine()->dirExists($dirpath);
    }

    public function remove($path)
    {
        $path = $this->getAbsoluteGameContentDir() . $path;

        return PHPSeclibWrapper::getFromMachineEntity($this->getMachine())
                ->remove($path);
    }

    public function rename($oldName, $newName)
    {
        return PHPSeclibWrapper::getFromMachineEntity($this->getMachine())
                ->getSFTP()
                ->rename($oldName, $newName)
        ;
    }

    public function createDirectory($dirpath)
    {
        $dirpath = $this->getAbsoluteGameContentDir() . $dirpath;

        return PHPSeclibWrapper::getFromMachineEntity($this->getMachine())
                ->createDirectory($dirpath);
    }
    
    public function getServerLogs()
    {
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        
        return $sec->getScreenContent($this->getScreenName());
    }
    
    public function getInstallLogs()
    {
        $sec = PHPSeclibWrapper::getFromMachineEntity($this->getMachine());
        
        return $sec->getScreenContent($this->getInstallScreenName());
    }
    
    public function isInstallationEnded()
    {
        return $this->installationStatus >= 101;
    }
    
    public function finalizeInstallation(\Twig_Environment $twig)
    {
        $this->uploadShellScripts($twig);
        $this->uploadDefaultServerConfigurationFile();
        $this->removeInstallationFiles();
        
        $this->setInstallationStatus(101);
    }
    
    public function installPlugin(\Twig_Environment $twig, Plugin $plugin)
    {
        throw new NotImplementedException();
    }
    
    public function uninstallPlugin(\Twig_Environment $twig, Plugin $plugin)
    {
        throw new NotImplementedException();
    }
    
    abstract public function uploadShellScripts(\Twig_Environment $twig);
    
    abstract public function uploadDefaultServerConfigurationFile();
    
    abstract public function removeInstallationFiles();
    
    abstract public function regenerateScripts(\Twig_Environment $twig);
}
