# A Concrete CMS Package: Macareux Content Importer

A Concrete CMS package to import contents from external web pages or internal html files

## Getting Started

### Steps to import contents

#### 1. Create a batch

After installing the package, you can create a import batch from the dashboard page.
Go to "Dashboard > System & Settings > Content Importer > Batches" and click on "Add Batch" button.
Provide a name and required information to create a batch, then click on "Add Batch" button.

#### 2. Set up selectors

After creating a batch, you can set up selectors to extract contents from the source page.
Composer form items is listed, you can add selectors by clicking on "Set Selector" button.
You can use several ways to extract contents from the source page, such as Xpath, CSS selector, or file name, etc.
Also, you can select the type of the content, such as inner html, inner text, or attribute value.
For example, if you want to extract the title of the source page, you may use the CSS selector `h1` and select the type `inner text`.
Or, if you want to get og:image meta tag, you may use the CSS selector `meta[property=og\:image]` and select the type `attribute` and provide the attribute name `content`.
To test the selectors, you can use the "Preview" button to see the extracted contents.

#### 3. Set up transformers

After setting up selectors, you can set up transformers.
With transformers, you can transform the extracted contents like:

- Generate a slug from the title
- Search specific content with regular expression and replace it with another content
- Import images files from the extracted html content
- Get topics from the extracted content
- etc.

Some transformers allow you to preview the result, so you can test the transformers before importing the contents.

#### 4. Import contents

After setting up selectors and transformers, you can import the contents by clicking on "Import" button.
You can skip the contents that are already imported.

### Batch logs

All import results are logged to prevent duplicate imports.
You can see the logs of the recent batch imports from the "Dashboard > System & Settings > Content Importer > Batches > Batch Logs" page.
Also, you can download the log file as a CSV file.

### File logs

All import results of the files are logged to prevent duplicate uploading to the file manager.
You can see the logs of the recent file imports from the "Dashboard > System & Settings > Content Importer > Batches > File Logs" page.
Also, you can download the log file as a CSV file.

## Creating a custom transformer / publisher

You can create a custom transformer or publisher to transform the extracted contents or publish the contents by your own way.
You may want to create a custom transformer to convert the extracted contents by more complex logic or to support attribute types not supported by the package (only image file type is supported by default).
You may want to create a custom publisher to support block types not supported by the package (only content block type is supported by default).

### Creating a transformer

First, create a class by implementing the `\Macareux\ContentImporter\Transformer\TransformerInterface` interface.
The interface will be stored in the database as a serialized string, so you may want to implement the `__sleep` and `__wakeup` methods to serialize and unserialize the object properly.

You can use `\Macareux\ContentImporter\Traits\ImageFileTransformerTrait` to create a transformer that downloads files from the extracted contents.

Then, register the transformer to the transformer manager.

```php
$transformerManager = $this->app->make(\Macareux\ContentImporter\Transformer\TransformerManager::class);
$transformerManager->registerTransformer(new YourCustomTransformer());
```

### Creating a publisher

First, create a class by implementing the `\Macareux\ContentImporter\Publisher\Block\BlockPublisherInterface` interface.

Then, register the publisher to the publisher manager.

```php
$publisherManager = $this->app->make(\Macareux\ContentImporter\Publisher\Block\BlockPublisherManager::class);
$publisherManager->registerPublisher(new YourCustomPublisher());
```

## License

MIT License
