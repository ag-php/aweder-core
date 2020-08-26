<?php

namespace App\Service;

use App\Category;
use App\Contract\Repositories\CategoryContract as CategoryRepository;
use App\Contract\Service\CategoryContract;
use App\Merchant;
use DB;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;

class CategoryService implements CategoryContract
{
    /**
     * @var CategoryRepository
     */
    private CategoryRepository $categoryRepository;

    private FilesystemManager $fileSystemManager;

    public function __construct(
        CategoryRepository $categoryRepository,
        FilesystemManager $fileSystemManager
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->fileSystemManager = $fileSystemManager;
    }

    public function addCategoriesAndSubCategoriesToMerchantFromPayload(Merchant $merchant, array $payload): bool
    {
        DB::beginTransaction();
        $order = $this->categoryRepository->getCategoryMaxOrderForMerchant($merchant);

        $category = new Category([
            'title' => $payload['title'],
            'visible' => filter_var($payload['visible'], FILTER_VALIDATE_BOOLEAN),
            'order' => $order + 1
        ]);

        if (!$this->categoryRepository->addCategoryToMerchant($merchant, $category)) {
            return false;
        }

        if (isset($payload['image'])) {
            if (!$this->addImageToCategory($category, $payload['image'])) {
                DB::rollBack();
                return false;
            }
        }

        if (isset($payload['subCategories'])) {
            $subcategories = explode(',', $payload['subCategories']);

            foreach ($subcategories as $subcategoryTitle) {
                $subcategory = new Category([
                    'title' => $subcategoryTitle
                ]);

                if (!$this->categoryRepository->addSubCategoryToCategory($category, $subcategory)) {
                    DB::rollBack();
                    return false;
                }
            }
        }

        DB::commit();
        return true;
    }

    public function addImageToCategory(Category $category, UploadedFile $imageFile): bool
    {
        if (!$path = $this->fileSystemManager->cloud()->putFile('category', $imageFile)) {
            return false;
        }

        return $category->update(['image' => $path]);
    }
}
