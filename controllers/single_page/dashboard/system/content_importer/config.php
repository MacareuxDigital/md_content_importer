<?php

namespace Concrete\Package\MdContentImporter\Controller\SinglePage\Dashboard\System\ContentImporter;

use Concrete\Core\Config\Repository\Liaison;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Controller\DashboardPageController;
use GuzzleHttp\Cookie\SetCookie;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Config extends DashboardPageController
{
    public function view()
    {
        $config = $this->getConfig();
        if ($config) {
            $cookie_value = $config->get('concrete.http.cookie');
            $this->set('cookie_value', $cookie_value);
        }
    }

    public function submit(): ?RedirectResponse
    {
        if (!$this->token->validate('update_config')) {
            $this->error->add($this->token->getErrorMessage());
        }

        // Validate the cookie value
        $cookie_value = trim($this->post('cookie_value'));

        if (!$this->error->has()) {
            $config = $this->getConfig();
            if ($config) {
                $config->save('concrete.http.cookie', $cookie_value);
                $this->flash('success', t('The settings has been successfully updated.'));
            }

            return $this->buildRedirect([$this->getPageObject()]);
        }

        return null;
    }

    protected function getConfig(): ?Liaison
    {
        /** @var PackageService $packageService */
        $packageService = $this->app->make(PackageService::class);
        $package = $packageService->getClass('md_content_importer');
        if ($package) {
            return $package->getFileConfig();
        }

        return null;
    }
}