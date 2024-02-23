<?php

namespace Site\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * This view helper class displays a menu bar.
 */
class Menu extends AbstractHelper
{
    /**
     * Menu items array.
     * @var array 
     */
    protected $items = [];

    /**
     * Active item's ID.
     * @var string  
     */
    protected $activeItemId = '';

    /**
     * Constructor.
     * @param array $items Menu items.
     */
    public function __construct($items = [])
    {
        $this->items = $items;
    }

    /**
     * Sets menu items.
     * @param array $items Menu items.
     */
    public function setItems($items)
    {
        $this->items = $items;
    }

    /**
     * Sets ID of the active items.
     * @param string $activeItemId
     */
    public function setActiveItemId($activeItemId)
    {
        $this->activeItemId = $activeItemId;
    }

    /**
     * Renders the menu.
     * @return string HTML code of the menu.
     */
    public function render($special = null)
    {
        if (count($this->items) == 0) {
            return '';
        } // Do nothing if there are no items.

        $result = "";

        // Render items
        foreach ($this->items as $item) {
            if (!isset($item['float']) || $item['float'] == 'left') {
                $result .= $this->renderItem($item);
            }
        }

        // Render items
        foreach ($this->items as $item) {
            if (isset($item['float']) && $item['float'] == 'right') {
                $result .= $this->renderItem($item);
            }
        }
        return $result;
    }

    /**
     * Renders an item.
     * @param array $item The menu item info.
     * @return string HTML code of the item.
     */
    protected function renderItem($item)
    {
        $id = isset($item['id']) ? $item['id'] : '';
        $isActive = ($id == $this->activeItemId);
        $label = isset($item['label']) ? $item['label'] : '';

        $result = '';

        $escapeHtml = $this->getView()->plugin('escapeHtml');

        if (isset($item['dropdown'])) {

            $dropdownItems = $item['dropdown'];
            ob_start();
?>
            <li class="dropdown">
                <a class="nav-link d-flex align-items-center gap-2 <?= ($isActive ? 'active' : '') ?> dropdown-toggle" <?= ($isActive ? 'aria-current="page"' : '') ?> id="menu_dropdown_<?= $label ?>" data-toggle="dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi-<?= $item['icon'] ?>"></i>
                    <?= $escapeHtml($label) ?>
                </a>
                <ul class="dropdown-menu" aria-labelledby="menu_dropdown_<?= $label ?>">
                    <?php foreach ($dropdownItems as $item) {
                        if (!is_null($item)) {
                            $link = isset($item['link']) ? $item['link'] : '#';
                            $label = isset($item['label']) ? $item['label'] : ''; ?>
                            <li> <a class="dropdown-item nav-link" href="<?= $escapeHtml($link); ?>"><?= $escapeHtml($label) ?></a></li>
                    <?php }
                    } ?>
                </ul>
            </li>
        <?php
            $result .= ob_get_clean();
        } else {
            $link = isset($item['link']) ? $item['link'] : '#';
            ob_start();
        ?>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center gap-2 <?= ($isActive ? 'active' : '') ?>" <?= ($isActive ? 'aria-current="page"' : '') ?> href="<?= $escapeHtml($link); ?>">
                    <i class="bi-<?= $item['icon'] ?>"></i>
                    <?= $escapeHtml($label) ?>
                </a>
            </li>
<?php
            $result .= ob_get_clean();
        }

        return $result;
    }
}
