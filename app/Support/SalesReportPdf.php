<?php

namespace App\Support;

class SalesReportPdf
{
    public static function render(array $report): string
    {
        $pages = [];
        $rows = $report['records'];
        $chunks = $rows->chunk(20);
        if ($chunks->isEmpty()) $chunks = collect([collect()]);
        foreach ($chunks as $pageIndex => $chunk) {
            $commands = [];
            self::rect($commands, 0, 535, 842, 60, [0.09, 0.20, 0.42]);
            self::text($commands, 35, 565, 'MARKETLY', 18, [1,1,1], true);
            self::text($commands, 35, 545, 'SALES ANALYTICS AND REPORT', 9, [0.79,0.86,0.97], true);
            self::text($commands, 575, 565, 'Date range: '.$report['range_label'], 9, [1,1,1]);
            self::text($commands, 575, 548, 'Generated: '.$report['generated_at']->format('M j, Y H:i'), 9, [1,1,1]);
            if ($pageIndex === 0) {
                self::text($commands, 35, 515, 'Sales summary', 12, [0.09,0.20,0.42], true);
                $summary = $report['summary'];
                $cards = [
                    ['TOTAL SALES', 'Rs. '.number_format($summary['total_sales'],2)],
                    ['TOTAL ORDERS', number_format($summary['total_orders'])],
                    ['TOTAL REVENUE', 'Rs. '.number_format($summary['total_revenue'],2)],
                    ['AVERAGE ORDER VALUE', 'Rs. '.number_format($summary['average_order_value'],2)],
                    ['PRODUCTS SOLD', number_format($summary['products_sold'])],
                ];
                $x = 35;
                foreach ($cards as [$label,$value]) {
                    self::rect($commands,$x,458,150,43,[0.95,0.97,1]);
                    self::text($commands,$x+8,485,$label,6,[0.31,0.40,0.55],true);
                    self::text($commands,$x+8,468,$value,10,[0.09,0.20,0.42],true);
                    $x += 155;
                }
                $tableY = 430;
            } else {
                self::text($commands,35,515,'Sales report (continued)',12,[0.09,0.20,0.42],true);
                $tableY = 488;
            }
            self::rect($commands, 35, $tableY-20, 772, 22, [0.91,0.94,0.98]);
            $cols = [35,90,177,290,500,602,700];
            foreach (['ORDER ID','DATE','CUSTOMER','PRODUCTS','PAYMENT','STATUS','AMOUNT'] as $i=>$heading) self::text($commands,$cols[$i]+4,$tableY-13,$heading,7,[0.15,0.24,0.39],true);
            $y = $tableY - 38;
            foreach ($chunk as $order) {
                self::text($commands,$cols[0]+4,$y,'#'.$order->id,7,[0.16,0.21,0.30]);
                self::text($commands,$cols[1]+4,$y,\Illuminate\Support\Carbon::parse($order->created_at)->format('Y-m-d'),7,[0.16,0.21,0.30]);
                self::text($commands,$cols[2]+4,$y,self::short($order->customer_name ?? 'Guest',19),7,[0.16,0.21,0.30]);
                self::text($commands,$cols[3]+4,$y,self::short($order->products,36),7,[0.16,0.21,0.30]);
                self::text($commands,$cols[4]+4,$y,self::short($order->payment_method,15),7,[0.16,0.21,0.30]);
                self::text($commands,$cols[5]+4,$y,self::short(ucfirst($order->status ?? 'pending'),13),7,[0.16,0.21,0.30]);
                self::text($commands,$cols[6]+4,$y,number_format((float)$order->total_amount,2),7,[0.16,0.21,0.30]);
                self::line($commands,35,$y-7,807,$y-7,[0.91,0.93,0.96]);
                $y -= 19;
            }
            self::text($commands,35,22,'Marketly · Confidential sales report',7,[0.48,0.54,0.63]);
            self::text($commands,760,22,'Page '.($pageIndex+1).' of '.$chunks->count(),7,[0.48,0.54,0.63]);
            $pages[] = implode("\n",$commands);
        }

        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
        $pageIds = [];
        foreach ($pages as $index=>$content) {
            $pageId = 5 + $index*2; $streamId = $pageId+1; $pageIds[] = $pageId.' 0 R';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$streamId.' 0 R >>';
            $objects[$streamId] = '<< /Length '.strlen($content)." >>\nstream\n".$content."\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ',$pageIds).'] /Count '.count($pages).' >>';
        ksort($objects); $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets = [0];
        foreach ($objects as $id=>$object) { $offsets[$id] = strlen($pdf); $pdf .= $id." 0 obj\n".$object."\nendobj\n"; }
        $xref = strlen($pdf); $size = max(array_keys($objects))+1; $pdf .= "xref\n0 $size\n0000000000 65535 f \n";
        for ($i=1;$i<$size;$i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i] ?? 0)."\n";
        return $pdf."trailer\n<< /Size $size /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }

    private static function text(array &$commands, float $x, float $y, string $value, int $size, array $color, bool $bold=false): void
    {
        $value = self::escape(self::short($value, 100));
        $args = array_merge([$bold?'F2':'F1',$size],$color,[$x,$y,$value]);
        $commands[] = sprintf('BT /%s %d Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET', ...$args);
    }
    private static function rect(array &$commands,float $x,float $y,float $w,float $h,array $color): void { $args=array_merge($color,[$x,$y,$w,$h]); $commands[]=sprintf('%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f',...$args); }
    private static function line(array &$commands,float $x1,float $y1,float $x2,float $y2,array $color): void { $args=array_merge($color,[$x1,$y1,$x2,$y2]); $commands[]=sprintf('%.3f %.3f %.3f RG %.2f %.2f m %.2f %.2f l S',...$args); }
    private static function short(?string $value,int $limit): string { $value = trim(preg_replace('/\s+/u',' ',(string)$value)); return mb_strlen($value)>$limit ? mb_substr($value,0,$limit-1).'…' : $value; }
    private static function escape(string $value): string { $encoded = iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$value); return str_replace(['\\','(',')',"\r","\n"],['\\\\','\\(','\\)',' ',' '],$encoded===false?$value:$encoded); }
}
