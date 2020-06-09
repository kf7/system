<?php

namespace Kohana7\HTML;

/**
 * HTML table.
 */
abstract class AbstractTable
{
    /**
     * @var array Table attributes.
     */
    protected $attributes = [];
    /**
     * @var string Table caption.
     */
    protected $caption;
    /**
     * @var array Table columns.
     */
    protected $columns = [];
    /**
     * @var array `<tbody>` rows.
     */
    protected $rows = [];
    /**
     * @var bool Whether or not to automatically create the table header.
     */
    protected $auto_heading = true;

    /**
     * Set the template from the table config file if it exists.
     *
     * @param Col[] $cols Columns
     */
    public function __construct(array $columns)
    {
        $this->setTemplate($template);
    }

    /**
     * Set the template.
     *
     * @param array $template
     * @return $this
     */
    public function setTemplate(array $template)
    {
        $this->template = $template + $this->getDefaultTemplate();
        return $this;
    }

    /**
     * Return default template.
     *
     * @return array
     */
    protected function getDefaultTemplate(): array
    {
        return [
            'table_open' => '<table class="table">',
            'header_open' => '<thead>',
            'header_close' => '</thead>',
            'heading_row_start' => '<tr>',
            'heading_row_end' => '</tr>',
            'heading_cell_start' => '<th>',
            'heading_cell_end' => '</th>',
            'tbody_open' => '<tbody>',
            'tbody_close' => '</tbody>',
            'footer_open' => '<tfoot>',
            'footer_close' => '</tfoot>',
            'row_start' => '<tr>',
            'row_end' => '</tr>',
            'cell_start' => '<td>',
            'cell_end' => '</td>',
            'row_alt_start' => '<tr>',
            'row_alt_end' => '</tr>',
            'cell_alt_start' => '<td>',
            'cell_alt_end' => '</td>',
            'table_close' => '</table>',
        ];
    }

    /**
     * Set the table heading
     *
     * @param string[] $headers
     * @return $this
     */
    public function setHeading(string ...$headers)
    {
        $this->heading = $this->normalize($headers);
        return $this;
    }

    /**
     * Ensures a standard associative array format for all cell data.
     *
     * @param array $values
     * @return array
     */
    protected function normalize(array $values): array
    {
        // if there is no `$values[0]`, skip this and treat as an associative array
        if (isset($values[0]) && count($values) == 1 && is_array($values[0]) && !isset($values[0]['data'])) {
            $values = $values[0];
        }
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = ['data' => $value];
            }
        }
        return $values;
    }

    /**
     * Set columns. Takes a one-dimensional array as input and creates a multi-dimensional array with a depth equal to
     * the number of columns. This allows a single array with many elements to be displayed in a table that has a
     * fixed column count.
     *
     * @param array $data
     * @param int $column_limit
     * @return array
     */
    public function makeColumns(array $data, int $column_limit): array
    {
        // turn off the auto-heading feature since it's doubtful we will want headings from a one-dimensional array
        $this->auto_heading = false;
        if ($column_limit < 1) {
            return $data;
        }
        $columns = [];
        do {
            $column = array_splice($data, 0, $column_limit);
            if (count($column) < $column_limit) {
                for ($i = count($column); $i < $column_limit; $i++) {
                    $column[] = $this->empty_cell;
                }
            }
            $columns[] = $column;
        } while ($data);

        return $columns;
    }

    /**
     * Set default content of empty cells.
     *
     * @return $this
     */
    public function setEmptyCell(string $value)
    {
        $this->empty_cell = $value;
        return $this;
    }

    /**
     * Add a table row
     *
     * Can be passed as an array or discreet params
     *
     * @param array $row
     * @return $this
     */
    public function addRow(...$row)
    {
        $this->rows[] = $this->normalize($row);
        return $this;
    }

    /**
     * Add a table caption
     *
     * @param string $caption
     * @return $this
     */
    public function setCaption(string $caption)
    {
        $this->caption = $caption;
        return $this;
    }

    /**
     * Generate the table.
     *
     * @param array $data Data of body rows
     * @return string
     */
    public function generate(array $data = [])
    {
        if ($data) {
            $this->setFromArray($data);
        }
        // compile and validate the template date
        $this->compileTemplate();

        // build the table
        $out = $this->template['table_open'] . $this->eol;
        // add any caption here
        if ($this->caption) {
            $out .= '<caption>' . $this->caption . '</caption>' . $this->eol;
        }

        // Is there a table heading to display?
        if (!empty($this->heading)) {
            $out .= $this->template['thead_open'] . $this->eol . $this->template['heading_row_start'] . $this->eol;
            foreach ($this->heading as $heading) {
                $temp = $this->template['heading_cell_start'];
                foreach ($heading as $key => $value) {
                    if ($key !== 'data') {
                        $temp = str_replace('<th', '<th ' . $key . '="' . $value . '"', $temp);
                    }
                }
                $out .= $temp . ($heading['data'] ?? '') . $this->template['heading_cell_end'];
            }
            $out .= $this->template['heading_row_end'] . $this->eol;
            $out .= $this->template['thead_close'] . $this->eol;
        }
        // Build the table rows
        if (!empty($this->rows)) {
            $out .= $this->template['tbody_open'] . $this->eol;
            $i = 1;
            foreach ($this->rows as $row) {
                if (!is_array($row)) {
                    break;
                }
                // We use modulus to alternate the row colors
                $name = fmod($i++, 2) ? '' : 'alt_';
                $out .= $this->template['row_' . $name . 'start'] . $this->eol;
                foreach ($row as $cell) {
                    $temp = $this->template['cell_' . $name . 'start'];
                    foreach ($cell as $key => $val) {
                        if ($key !== 'data') {
                            $temp = str_replace('<td ', '<td ' . $key . '="' . $val . '"', $temp);
                        }
                    }
                    $cell = $cell['data'] ?? '';
                    $out .= $temp;
                    if ($cell === '' or $cell === null) {
                        $out .= $this->empty_cell;
                    } elseif (isset($this->callback)) {
                        $out .= call_user_func($this->callback, $cell);
                    } else {
                        $out .= $cell;
                    }
                    $out .= $this->template['cell_' . $name . 'end'];
                }
                $out .= $this->template['row_' . $name . 'end'] . $this->eol;
            }
            $out .= $this->template['tbody_close'] . $this->eol;
        }
        $out .= $this->template['table_close'];
        // clear table class properties before generating the table.
        $this->clear();
        return $out;
    }

    /**
     * Set table data from an array.
     *
     * @param array $data
     */
    protected function setFromArray(array $data): void
    {
        if ($this->auto_heading === true && empty($this->heading)) {
            $this->heading = $this->normalize(array_shift($data));
        }
        foreach ($data as &$row) {
            $this->rows[] = $this->normalize($row);
        }
    }

    /**
     * Compile template.
     */
    protected function compileTemplate(): void
    {
        if ($this->template === null) {
            $this->template = $this->getDefaultTemplate();
            return;
        }

        $this->temp = $this->_default_template();
        foreach (array_keys($this->getDefaultTemplate()) as $key) {
            if (!isset($this->template[$key])) {
                $this->template[$key] = $this->temp[$key];
            }
        }
    }

    /**
     * Clears the table arrays. Useful if multiple tables are being generated.
     *
     * @return $this
     */
    public function clear()
    {
        $this->rows = [];
        $this->heading = [];
        $this->auto_heading = true;
        $this->caption = null;
        return $this;
    }
}
