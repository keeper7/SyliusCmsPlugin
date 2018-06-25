<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusCmsPlugin\Controller;

use BitBag\SyliusCmsPlugin\Entity\BlockInterface;
use FOS\RestBundle\View\View;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockController extends ResourceController
{
    public function renderBlockAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::SHOW);

        $code = $request->get('code');
        $blockResourceResolver = $this->get('bitbag_sylius_cms_plugin.resolver.block_resource');

        $block = $blockResourceResolver->findOrLog($code);

        if (null === $block) {
            return new Response();
        }

        $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $block);

        $view = View::create($block);

        $blockTemplateResolver = $this->get('bitbag_sylius_cms_plugin.resolver.block_template');

        $template = $request->get('template') ?? $blockTemplateResolver->resolveTemplate($block);

        if ($configuration->isHtmlRequest()) {
            $view
                ->setTemplate($template)
                ->setTemplateVar($this->metadata->getName())
                ->setData([
                    'configuration' => $configuration,
                    'metadata' => $this->metadata,
                    'resource' => $block,
                    $this->metadata->getName() => $block,
                ])
            ;
        }

        return $this->viewHandler->handle($configuration, $view);
    }

    public function previewAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::CREATE);
        $newResource = $this->newResourceFactory->create($configuration, $this->factory);

        $newResource->setType($request->get('type'));

        $form = $this->resourceFormFactory->create($configuration, $newResource);

        $form->handleRequest($request);

        /** @var BlockInterface $newResource */
        $newResource = $form->getData();

        $defaultLocale = $this->getParameter('locale');

        $newResource->setFallbackLocale($request->get('_locale', $defaultLocale));
        $newResource->setCurrentLocale($request->get('_locale', $defaultLocale));

        $blockTemplateResolver = $this->get('bitbag_sylius_cms_plugin.resolver.block_template');

        $view = View::create()
            ->setData([
                'resource' => $newResource,
                $this->metadata->getName() => $newResource,
                'blockTemplate' => $blockTemplateResolver->resolveTemplate($newResource),
            ])
            ->setTemplate($configuration->getTemplate(ResourceActions::CREATE . '.html'))
        ;

        return $this->viewHandler->handle($configuration, $view);
    }
}
