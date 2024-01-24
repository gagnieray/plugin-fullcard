<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Member full card PDF
 *
 * PHP version 5
 *
 * Copyright © 2016-2023 The Galette Team
 *
 * This file is part of Galette (http://galette.tuxfamily.org).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  IO
 * @package   GaletteFullcard
 *
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2016-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @version   SVN: $Id$
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.9dev - 2016-03-02
 */

namespace GaletteFullcard;

use Galette\Entity\PdfModel;
use Galette\IO\PdfAdhesionForm;
use Galette\Core\Preferences;
use Galette\Core\Db;
use Galette\Entity\Adherent;
use Galette\IO\Pdf;

/**
 * Member full card PDF
 *
 * @category  IO
 * @name      PDF
 * @package   Galette
 * @abstract  Class for expanding TCPDF.
 * @author    Johan Cwiklinski <johan@x-tnd.be>
 * @copyright 2016-2023 The Galette Team
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0 or (at your option) any later version
 * @link      http://galette.tuxfamily.org
 * @since     Available since 0.9dev - 2016-03-02
 */

class PdfFullcard extends PdfAdhesionForm
{
    /**
     * Main constructor
     *
     * @param ?Adherent   $adh   Adherent
     * @param Db          $zdb   Database instance
     * @param Preferences $prefs Preferences instance
     */
    public function __construct(Adherent $adh = null, Db $zdb, Preferences $prefs)
    {
        $this->adh = $adh;
        $this->prefs = $prefs;
        $this->filename = _T('fullcard', 'fullcard') . '.pdf';
        parent::__construct($adh, $zdb, $prefs);
        $this->init();
        $this->drawCard();
    }

    /**
     * Get model
     *
     * @return ?PdfModel
     */
    protected function getModel(): ?PdfModel
    {
        //override default PdfAdhesionForm model
        return null;
    }

    /**
     * Initialize PDF
     *
     * @return void
     */
    private function init(): void
    {
        // Set document information
        $this->SetTitle(_T('Member\'s full card', 'fullcard'));
        $this->SetSubject(_T('Generated by Galette', 'fullcard'));
        $this->SetKeywords(_T('Labels', 'fullcard'));

        $this->setMargins(10, 10);

        // Show full page
        $this->SetDisplayMode('fullpage');

        // Disable Auto Page breaks
        $this->SetAutoPageBreak(false, 20);
    }

    /**
     * Draw member cards
     *
     * @return void
     */
    private function drawCard(): void
    {
        $member = $this->adh;

        define('FULLCARD_FONT', Pdf::FONT_SIZE - 2);
        $this->SetFont(Pdf::FONT, '', FULLCARD_FONT);
        $this->SetTextColor(0, 0, 0);

        $this->PageHeader(_T("Adhesion form"));

        $this->SetDrawColor(180, 180, 180);
        $this->SetLineWidth(0.1);

        $this->Ln(10);
        $this->Line($this->GetX(), $this->GetY(), 200, $this->GetY());
        $this->Ln(2);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont(Pdf::FONT, '', FULLCARD_FONT - 1);
        $this->MultiCell(0, 4, _T("Complete the following form and send it with your funds, in order to complete your subscription."), 0, 'L');

        $this->ln(2);
        $this->SetFont(Pdf::FONT, '', FULLCARD_FONT);
        $this->SetX(100);
        $this->MultiCell(0, 4, $this->prefs->getPostalAddress(), 0, 'L');
        $this->Ln(3);
        $this->Line($this->GetX(), $this->GetY(), 200, $this->GetY());

        $this->Ln(10);
        $this->SetFont(Pdf::FONT, '', FULLCARD_FONT + 2);

        //let's draw all fields
        $y = $this->GetY() + 1;
        $this->Write(5, _T("Required membership:"));
        $this->SetX($this->GetX() + 5);
        $this->Rect($this->GetX(), $y, 3, 3);
        $this->SetX($this->GetX() + (($member === null) ? 3 : 0));
        $this->Cell(3, 5, ($member !== null && $member->status == 4) ? "X" : "", 0, 0, 'C');

        $this->Write(5, _T("Active member"));
        $this->SetX($this->GetX() + 5);
        $this->Rect($this->GetX(), $y, 3, 3);
        $this->SetX($this->GetX() + (($member === null) ? 3 : 0));
        $this->Cell(3, 5, ($member !== null && $member->status == 5) ? "X" : "", 0, 0, 'C');
        $this->Write(5, _T("Benefactor member"));
        $this->SetX($this->GetX() + 5);
        $this->Rect($this->GetX(), $y, 3, 3);
        $this->SetX($this->GetX() + 3);
        $this->Write(5, _T("Donation"));
        $this->Ln();
        $this->SetFont(Pdf::FONT, '', FULLCARD_FONT);
        $this->Write(4, _T("The minimum contribution for each type of membership are defined on the website of the association. The amount of donations are free to be decided by the generous donor."));
        $this->Ln(20);

        $this->SetFont(Pdf::FONT, '', FULLCARD_FONT + 2);
        $this->Cell(30, 7, _T("Politeness"), 0, 0, 'L');
        $title = '';
        if ($member !== null && $member->title) {
            $title = $member->title->long;
        }
        $this->Cell(0, 7, $title, 0, 1, 'L');
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);

        $this->Cell(30, 7, _T("Name"), 0, (($member === null) ? 1 : 0), 'L');
        if ($member !== null) {
            $this->Cell(0, 7, $member->name ?? '', 0, 1, 'L');
        }
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);
        $this->Cell(30, 7, _T("First name"), 0, (($member === null) ? 1 : 0), 'L');
        if ($member !== null) {
            $this->Cell(0, 7, $member->surname ?? '', 0, 1, 'L');
        }
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);
        $this->Cell(30, 7, _T("Company name") . " *", 0, (($member === null) ? 1 : 0), 'L');
        if ($member !== null) {
            $this->Cell(0, 7, $member->company_name ?? '', 0, 1, 'L');
        }

        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);

        $this->Cell(30, 7, _T("Address"), 0, (($member === null) ? 1 : 0), 'L');
        if ($member !== null) {
            $this->Cell(0, 7, $member->address, 0, 1, 'L');
        }
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);
        $this->SetY($this->GetY() + 7);
        if ($member !== null) {
            $this->Cell(0, 7, $member->address_continuation ?? '', 0, 1, 'L');
        }
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);
        $this->SetY($this->GetY() + 7);
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);

        $y = $this->GetY();
        $this->Cell(30, 7, _T("Zip Code"), 0, (($member === null) ? 1 : 0), 'L');
        if ($member !== null) {
            $this->Cell(0, 7, $member->getZipcode(), 0, 1, 'L');
        }
        $this->Line($this->GetX() + 30, $this->GetY() - 1, $this->GetX() + 30 + 15, $this->GetY() - 1);
        $this->SetY($y);
        $this->SetX($this->GetX() + 30 + 15 + 5);
        $this->Cell(30, 7, _T("City"), 0, (($member === null) ? 1 : 0), 'L');
        if ($member !== null) {
            $this->Cell(0, 7, $member->getTown(), 0, 1, 'L');
        }
        $this->Line($this->GetX() + 30 + 15 + 30, $this->GetY() - 1, 190, $this->GetY() - 1);

        $this->Cell(30, 7, _T("Country"), 0, (($member === null) ? 1 : 0), 'L');
        if ($member !== null) {
            $this->Cell(0, 7, $member->getCountry(), 0, 1, 'L');
        }
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);

        $this->Cell(30, 7, _T("Email address"), 0, (($member === null) ? 1 : 0), 'L');
        if ($member !== null) {
            $this->Cell(0, 7, $member->getEmail(), 0, 1, 'L');
        }
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);

        $this->Cell(30, 7, _T("Username") . " **", 0, (($member === null) ? 1 : 0), 'L');
        if ($member !== null) {
            $this->Cell(0, 7, $member->login ?? '', 0, 1, 'L');
        }
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);

        $this->Ln(6);
        $this->Cell(30, 7, _T("Amount"), 0, 1, 'L');
        $this->Line($this->GetX() + 30, $this->GetY() - 1, 190, $this->GetY() - 1);

        $this->Ln(10);
        $this->Write(
            4,
            preg_replace(
                '/%s/',
                $this->prefs->pref_nom,
                _T("Hereby, I agree to comply to %s association statutes and its rules.")
            )
        );
        $this->Ln(10);
        $this->Cell(64, 5, _T("At ", "fullcard"), 0, 0, 'L');
        $this->Cell(0, 5, _T("On            /            /            ", "fullcard"), 0, 1, 'L');
        $this->Ln(1);
        $this->Cell(0, 5, _T("Signature"), 0, 1, 'L');


        $this->SetY(260);
        $this->SetFont(Pdf::FONT, '', FULLCARD_FONT - 2);
        $this->Cell(0, 3, _T("* Only for compagnies"), 0, 1, 'R');
        $this->Cell(0, 3, _T("** Galette identifier, if applicable"), 0, 1, 'R');
    }
}
