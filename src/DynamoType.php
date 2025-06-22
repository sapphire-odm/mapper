<?php

namespace Sapphire\Mapper;

/**
 * @link https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_AttributeValue.html
 */
enum DynamoType: string
{
    // "S": "Hello"
    case STRING = 'S';

    // "N": "123.45"
    case NUMBER = 'N';

    // Type: Base64-encoded binary data object
    // "B": "dGhpcyB0ZXh0IGlzIGJhc2U2NC1lbmNvZGVk"
    case BINARY = 'B';

    // "BOOL": true
    case BOOL = 'BOOL';

    // "NULL": true
    case NULL = 'NULL';

    // "L": [ {"S": "Cookies"} , {"S": "Coffee"}, {"N": "3.14159"}]
    case LIST = 'L';

    // "M": {"Name": {"S": "Joe"}, "Age": {"N": "35"}}
    case MAP = 'M';

    // "SS": ["Giraffe", "Hippo" ,"Zebra"]
    case STRING_SET = 'SS';

    // "NS": ["42.2", "-19", "7.5", "3.14"]
    case NUMBER_SET = 'NS';

    // Type: Array of Base64-encoded binary data objects
    // "BS": ["U3Vubnk=", "UmFpbnk=", "U25vd3k="]
    case BINARY_SET = 'BS';
}
