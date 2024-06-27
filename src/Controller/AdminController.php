<?php
namespace App\Controller;

use App\Repository\CohortRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractWimsLoaderController
{
    public function __construct(
        private CohortRepository $cohortRepo,
    ) {}

    #[Route(path:"/admin/cohorts", name:"adminCohorts")]
    #[Template('web/admin.html.twig')]
    public function indexAdmin(Security $security): array
    {
        $data = $this->cohortRepo->findAllData();
        return [
            'data' => $data,
        ];
    }
}