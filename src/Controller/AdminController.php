<?php
namespace App\Controller;

use App\Repository\ClassesRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADM')]
class AdminController extends AbstractWimsLoaderController
{
    public function __construct(
        private ClassesRepository $classesRepo,
    ) {}

    #[Route(path:"/admin/", name:"admin")]
    #[Template('web/admin.html.twig')]
    public function indexAdmin(Security $security): array
    {
        $data = $this->classesRepo->findAllData();
        return [
            'data' => $data,
        ];
    }
}