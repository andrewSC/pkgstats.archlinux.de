<?php

namespace App\Controller;

use App\Entity\Module;
use App\Entity\Package;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ClientIdGenerator;
use App\Service\GeoIp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PostPackageListController extends AbstractController
{
    /** @var int */
    private $delay;
    /** @var int */
    private $count;
    /** @var bool */
    private $quiet = false;
    /** @var RouterInterface */
    private $router;
    /** @var GeoIp */
    private $geoIp;
    /** @var ClientIdGenerator */
    private $clientIdGenerator;
    /** @var UserRepository */
    private $userRepository;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param int $delay
     * @param int $count
     * @param RouterInterface $router
     * @param GeoIp $geoIp
     * @param ClientIdGenerator $clientIdGenerator
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        int $delay,
        int $count,
        RouterInterface $router,
        GeoIp $geoIp,
        ClientIdGenerator $clientIdGenerator,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->delay = $delay;
        $this->count = $count;
        $this->router = $router;
        $this->geoIp = $geoIp;
        $this->clientIdGenerator = $clientIdGenerator;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/post", methods={"POST"})
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request): Response
    {
        $pkgstatsver = str_replace('pkgstats/', '', $request->server->get('HTTP_USER_AGENT'));

        if (!in_array($pkgstatsver, ['2.3'])) {
            throw new BadRequestHttpException('Sorry, your version of pkgstats is not supported.');
        }

        $packages = array_unique(explode("\n", trim($request->request->get('packages'))));
        $packages = array_filter($packages);
        $packageCount = count($packages);

        $modules = array_unique(explode("\n", trim($request->request->get('modules'))));
        $modules = array_filter($modules);
        $moduleCount = count($modules);

        $arch = $request->request->get('arch');
        $cpuArch = $request->request->get('cpuarch', $arch);
        $mirror = $request->request->get('mirror', '');
        $this->quiet = $request->request->get('quiet') == 'true';

        if (!empty($mirror) && !preg_match('#^(?:https?|ftp)://\S+#', $mirror)) {
            $mirror = null;
        } elseif (!empty($mirror) && strlen($mirror) > 255) {
            throw new BadRequestHttpException($mirror . ' is too long.');
        } elseif (empty($mirror)) {
            $mirror = null;
        }
        if (!in_array($arch, ['x86_64'])) {
            throw new BadRequestHttpException($arch . ' is not a known architecture.');
        }
        if (!in_array($cpuArch, ['x86_64'])) {
            throw new BadRequestHttpException($cpuArch . ' is not a known architecture.');
        }
        if ($packageCount == 0) {
            throw new BadRequestHttpException('Your package list is empty.');
        }
        if ($packageCount > 10000) {
            throw new BadRequestHttpException('So, you have installed more than 10,000 packages?');
        }
        foreach ($packages as $package) {
            if (strlen($package) > 255 || !preg_match('/^[^-]+\S*$/', $package)) {
                throw new BadRequestHttpException($package . ' does not look like a valid package');
            }
        }
        if ($moduleCount > 5000) {
            throw new BadRequestHttpException('So, you have loaded more than 5,000 modules?');
        }
        foreach ($modules as $module) {
            if (strlen($module) > 255 || !preg_match('/^[\w\-]+$/', $module)) {
                throw new BadRequestHttpException($module . ' does not look like a valid module');
            }
        }

        $clientIp = $request->getClientIp();
        $countryCode = $this->geoIp->getCountryCode($clientIp);
        if (empty($countryCode)) {
            $countryCode = null;
        }

        $user = (new User())
            ->setIp($this->clientIdGenerator->createClientId($clientIp))
            ->setTime(time())
            ->setArch($arch)
            ->setCpuarch($cpuArch)
            ->setCountrycode($countryCode)
            ->setMirror($mirror)
            ->setPackages($packageCount)
            ->setModules($moduleCount);

        $this->checkIfAlreadySubmitted($user);

        $this->entityManager->transactional(
            function (EntityManagerInterface $entityManager) use ($user, $packages, $modules) {
                $entityManager->persist($user);

                foreach ($packages as $package) {
                    $entityManager->merge(
                        (new Package())
                            ->setPkgname($package)
                            ->setMonth(date('Ym', $user->getTime()))
                    );
                }

                foreach ($modules as $module) {
                    $entityManager->merge(
                        (new Module())
                            ->setName($module)
                            ->setMonth(date('Ym', $user->getTime()))
                    );
                }
            }
        );

        if (!$this->quiet) {
            $body = 'Thanks for your submission. :-)' . "\n" . 'See results at '
                . $this->router->generate('app_start_index', [], UrlGeneratorInterface::ABSOLUTE_URL)
                . "\n";
        } else {
            $body = '';
        }

        return new Response($body, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * @param User $user
     */
    private function checkIfAlreadySubmitted(User $user)
    {
        $submissionCount = $this->userRepository->getSubmissionCountSince(
            $user->getIp(),
            $user->getTime() - $this->delay
        );
        if ($submissionCount >= $this->count) {
            throw new BadRequestHttpException(
                'You already submitted your data ' . $this->count . ' times.'
            );
        }
    }
}
