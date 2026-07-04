<?php

namespace App\Enums;

enum CertificateStatus: string
{
    case Issued = 'Issued';
    case Revoked = 'Revoked';
}
