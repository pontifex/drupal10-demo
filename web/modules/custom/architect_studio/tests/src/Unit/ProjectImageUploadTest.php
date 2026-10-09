<?php

declare(strict_types=1);

namespace Drupal\Tests\architect_studio\Unit;

use Drupal\architect_studio\Form\ProjectEditForm;
use Drupal\architect_studio\Repository\ProjectRepository;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\FileInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Testy jednostkowe wgrywania i obsługi plików graficznych projektu.
 *
 * @group architect_studio
 */
class ProjectImageUploadTest extends UnitTestCase {

  /**
   * Test struktury pól formularza - obecność managed_file dla obrazu i rzutu.
   */
  public function testFormBuildContainsManagedFiles(): void {
    $projectRepo = $this->createMock(ProjectRepository::class);
    $projectRepo->method('getById')->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $fileUrlGenerator = $this->createMock(FileUrlGeneratorInterface::class);

    $form = new ProjectEditForm($projectRepo, $entityTypeManager, $fileUrlGenerator);
    $form->setStringTranslation($this->getStringTranslationStub());

    $formState = new FormState();
    $builtForm = $form->buildForm([], $formState, NULL);

    $this->assertArrayHasKey('media', $builtForm);
    $this->assertSame('managed_file', $builtForm['media']['image_upload']['#type']);
    $this->assertSame('public://projects/', $builtForm['media']['image_upload']['#upload_location']);
    $this->assertArrayHasKey('FileExtension', $builtForm['media']['image_upload']['#upload_validators']);

    $this->assertSame('managed_file', $builtForm['media']['floor_plan_upload']['#type']);
    $this->assertSame('public://projects/', $builtForm['media']['floor_plan_upload']['#upload_location']);
    $this->assertArrayHasKey('FileExtension', $builtForm['media']['floor_plan_upload']['#upload_validators']);
  }

  /**
   * Test zapisu z plikami managed_file - utrwalenie i generowanie URL.
   */
  public function testSubmitWithUploadedFiles(): void {
    $projectRepo = $this->createMock(ProjectRepository::class);
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $fileStorage = $this->createMock(EntityStorageInterface::class);
    $fileUrlGenerator = $this->createMock(FileUrlGeneratorInterface::class);

    $entityTypeManager->method('getStorage')->with('file')->willReturn($fileStorage);

    $imageFile = $this->createMock(FileInterface::class);
    $imageFile->expects($this->once())->method('setPermanent');
    $imageFile->expects($this->once())->method('save');
    $imageFile->method('getFileUri')->willReturn('public://projects/dom_nowoczesny.jpg');

    $planFile = $this->createMock(FileInterface::class);
    $planFile->expects($this->once())->method('setPermanent');
    $planFile->expects($this->once())->method('save');
    $planFile->method('getFileUri')->willReturn('public://projects/rzut_kondygnacji.svg');

    $fileStorage->method('load')->willReturnCallback(function (int|string $fid) use ($imageFile, $planFile) {
      if ((int) $fid === 101) {
        return $imageFile;
      }
      if ((int) $fid === 102) {
        return $planFile;
      }
      return NULL;
    });

    $fileUrlGenerator->method('generateString')->willReturnCallback(function (string $uri) {
      if ($uri === 'public://projects/dom_nowoczesny.jpg') {
        return '/sites/default/files/projects/dom_nowoczesny.jpg';
      }
      if ($uri === 'public://projects/rzut_kondygnacji.svg') {
        return '/sites/default/files/projects/rzut_kondygnacji.svg';
      }
      return $uri;
    });

    $projectRepo->expects($this->once())
      ->method('save')
      ->with($this->callback(function (array $data) {
        return $data['image_url'] === '/sites/default/files/projects/dom_nowoczesny.jpg'
          && $data['floor_plan_url'] === '/sites/default/files/projects/rzut_kondygnacji.svg'
          && $data['title'] === 'Nowy Dom z Plikami';
      }))
      ->willReturn(1);

    $form = new ProjectEditForm($projectRepo, $entityTypeManager, $fileUrlGenerator);
    $form->setStringTranslation($this->getStringTranslationStub());
    $messenger = $this->createMock(MessengerInterface::class);
    $form->setMessenger($messenger);

    $formState = new FormState();
    $formState->setValues([
      'title' => 'Nowy Dom z Plikami',
      'code' => 'DOM-PLIK-01',
      'category' => 'house',
      'area' => 140.5,
      'usable_area' => 125.0,
      'building_area' => 160.0,
      'roof_angle' => 35.0,
      'min_plot_width' => 18.0,
      'min_plot_length' => 22.0,
      'rooms_count' => 4,
      'price_digital' => 2900.0,
      'price_print' => 3400.0,
      'is_hidden' => 0,
      'description' => 'Opis testowego projektu',
      'image_upload' => [101],
      'image_url' => '/default/image.svg',
      'floor_plan_upload' => [102],
      'floor_plan_url' => '/default/plan.svg',
    ]);

    $formArray = [];
    $form->submitForm($formArray, $formState);
  }

  /**
   * Test zapisu ze ścieżkami tekstowymi (fallback), gdy brak plików.
   */
  public function testSubmitWithFallbackTextUrls(): void {
    $projectRepo = $this->createMock(ProjectRepository::class);
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $fileUrlGenerator = $this->createMock(FileUrlGeneratorInterface::class);

    $projectRepo->expects($this->once())
      ->method('save')
      ->with($this->callback(function (array $data) {
        return $data['image_url'] === '/modules/custom/architect_studio/images/custom_image.jpg'
          && $data['floor_plan_url'] === '/modules/custom/architect_studio/images/custom_plan.svg'
          && $data['title'] === 'Projekt ze ścieżkami';
      }))
      ->willReturn(2);

    $form = new ProjectEditForm($projectRepo, $entityTypeManager, $fileUrlGenerator);
    $form->setStringTranslation($this->getStringTranslationStub());
    $messenger = $this->createMock(MessengerInterface::class);
    $form->setMessenger($messenger);

    $formState = new FormState();
    $formState->setValues([
      'title' => 'Projekt ze ścieżkami',
      'code' => 'DOM-URL-01',
      'category' => 'house',
      'area' => 150.0,
      'usable_area' => 130.0,
      'building_area' => 170.0,
      'roof_angle' => 30.0,
      'min_plot_width' => 19.0,
      'min_plot_length' => 24.0,
      'rooms_count' => 5,
      'price_digital' => 3100.0,
      'price_print' => 3600.0,
      'is_hidden' => 0,
      'description' => 'Opis projektu z bezpośrednimi adresami',
      'image_upload' => [],
      'image_url' => '/modules/custom/architect_studio/images/custom_image.jpg',
      'floor_plan_upload' => [],
      'floor_plan_url' => '/modules/custom/architect_studio/images/custom_plan.svg',
    ]);

    $formArray = [];
    $form->submitForm($formArray, $formState);
  }

}
